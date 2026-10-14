<?php

namespace App\Services\Calendar;

use App\Ai\AiContext;
use App\Ai\Tasks\NameProcessesTask;
use App\Enums\ProcessKind;
use App\Enums\ProcessSource;
use App\Models\CalendarConnection;
use App\Models\CalendarEvent;
use App\Models\Membership;
use App\Models\Process;
use App\Services\Ai\AiGateway;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Turns the calendar's patterns into processes for V6·d (09 · 9.3): matched
 * to the existing ones by series or name, never duplicated (09 · 9.5); the
 * AI suggests names, types and subprocesses for the new ones.
 */
class CalendarDetector
{
    public function __construct(
        private PatternFinder $patterns,
        private AiGateway $ai,
    ) {}

    public function detect(CalendarConnection $connection): void
    {
        $events = $connection->events()->where('starts_at', '<=', now())->get()->map(fn (CalendarEvent $event) => new CalendarEventData(
            externalId: $event->external_id,
            seriesKey: $event->series_key,
            title: $event->title,
            description: $event->description,
            startsAt: $event->starts_at->toImmutable(),
            endsAt: $event->ends_at?->toImmutable(),
            attendees: $event->attendees ?? [],
        ))->all();

        $found = $this->patterns->find($events);
        $created = collect();

        DB::transaction(function () use ($connection, $found, $events, &$created): void {
            foreach ($found['candidates'] as $candidate) {
                [$process, $isNew] = $this->upsert($connection, $candidate);
                $this->addParticipants($process, $candidate['attendees']);
                $this->addOccasions($connection, $process, $candidate['events']);

                if ($isNew) {
                    $created->push(['process' => $process, 'candidate' => $candidate]);
                }
            }

            $connection->update([
                'event_count' => count($events),
                'one_off_count' => $found['one_off'],
                'unlinked_count' => $found['unlinked'],
            ]);
        });

        if ($created->isNotEmpty()) {
            $this->suggestNames($connection, $created);
        }
    }

    /**
     * The new occasions of the accepted processes after a refresh (09 · 9.4).
     * No new pattern is suggested while the diagnosis runs.
     */
    public function addNewOccasions(CalendarConnection $connection): void
    {
        $processes = Process::query()
            ->whereBelongsTo($connection->organization)
            ->whereNotNull('calendar_series_key')
            ->whereNotNull('accepted_at')
            ->get();

        foreach ($processes as $process) {
            $events = $this->eventsOf($connection, (string) $process->calendar_series_key);
            $this->addOccasions($connection, $process, $events);
        }
    }

    /**
     * 10 · 10.4: the calendar pattern goes into the process from the conversation.
     */
    public function merge(Process $calendarProcess, Process $into): void
    {
        DB::transaction(function () use ($calendarProcess, $into): void {
            $into->update([
                'calendar_series_key' => $calendarProcess->calendar_series_key,
                'rhythm' => $into->rhythm ?? $calendarProcess->rhythm,
                'rhythm_label' => $calendarProcess->rhythm_label,
                'duration_minutes' => $calendarProcess->duration_minutes,
                'headcount' => $calendarProcess->headcount ?? $into->headcount,
                'occurrence_label' => $calendarProcess->occurrence_label,
                'source' => ProcessSource::Calendar,
                'merge_candidate_id' => null,
            ]);

            $calendarProcess->occasions()->update(['process_id' => $into->id]);
            $calendarProcess->participants()->whereNotIn('email', $into->participants()->whereNotNull('email')->pluck('email'))->update(['process_id' => $into->id]);
            $calendarProcess->children()->update(['parent_id' => $into->id]);
            $calendarProcess->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @return array{0: Process, 1: bool}
     */
    private function upsert(CalendarConnection $connection, array $candidate): array
    {
        $existing = Process::query()
            ->whereBelongsTo($connection->organization)
            ->where(fn ($query) => $query
                ->where('calendar_series_key', $candidate['key'])
                ->orWhereRaw('lower(name) = ?', [mb_strtolower($candidate['title'])]))
            ->first();

        $stats = [
            'calendar_series_key' => $candidate['key'],
            'rhythm_label' => $candidate['rhythm_label'],
            'duration_minutes' => $candidate['duration_minutes'],
            'headcount' => $candidate['headcount'],
            'occurrence_label' => $candidate['occurrence_label'],
        ];

        if ($existing !== null) {
            $existing->update([
                ...$stats,
                'rhythm' => $existing->rhythm ?? $candidate['rhythm'],
                ...($existing->source === ProcessSource::Calendar ? ['confidence' => $candidate['confidence']] : []),
            ]);

            return [$existing, false];
        }

        return [Process::create([
            ...$stats,
            'organization_id' => $connection->organization_id,
            'name' => $candidate['title'],
            'rhythm' => $candidate['rhythm'],
            'kind' => ProcessKind::Routine,
            'source' => ProcessSource::Calendar,
            'confidence' => $candidate['confidence'],
        ]), true];
    }

    /**
     * Attendees matched to the organization's users by e-mail; the others by
     * name, "külsős" (11 · 11.3). Calendar participants are added once.
     *
     * @param  list<array{name: ?string, email: string}>  $attendees
     */
    private function addParticipants(Process $process, array $attendees): void
    {
        if ($process->participants()->where('source', 'calendar')->exists()) {
            return;
        }

        $members = Membership::query()
            ->whereBelongsTo($process->organization)
            ->with('user')
            ->get()
            ->keyBy(fn (Membership $membership) => $membership->user->email);

        foreach ($attendees as $attendee) {
            $membership = $members->get($attendee['email']);

            $process->participants()->create([
                'user_id' => $membership?->user_id,
                'name' => $membership === null ? $attendee['name'] : null,
                'email' => $attendee['email'],
                'source' => 'calendar',
            ]);
        }
    }

    /**
     * @param  iterable<CalendarEventData|CalendarEvent>  $events
     */
    private function addOccasions(CalendarConnection $connection, Process $process, iterable $events): void
    {
        foreach ($events as $event) {
            $key = $event instanceof CalendarEvent ? $event->external_id : $event->externalId;
            $model = $event instanceof CalendarEvent ? $event : $connection->events()->where('external_id', $key)->first();

            if ($model === null || $model->starts_at->isFuture()) {
                continue;
            }

            $process->occasions()->firstOrCreate(['calendar_event_key' => $key], [
                'calendar_event_id' => $model->id,
                'occurred_at' => $model->starts_at,
                'duration_minutes' => $model->durationMinutes(),
                'participant_count' => count($model->attendees ?? []),
                'source' => 'calendar',
            ]);
        }
    }

    /**
     * The events of a series key ("series:…" or "title:…").
     *
     * @return Collection<int, CalendarEvent>
     */
    private function eventsOf(CalendarConnection $connection, string $key): Collection
    {
        if (str_starts_with($key, 'series:')) {
            return $connection->events()->where('series_key', substr($key, strlen('series:')))->get();
        }

        $title = substr($key, strlen('title:'));

        return $connection->events()->whereNull('series_key')->get()
            ->filter(fn (CalendarEvent $event) => $this->patterns->normalize($event->title) === $title)
            ->values();
    }

    /**
     * Names, types and subprocesses from the AI; titles stay when it fails (09 · 9.3).
     *
     * @param  Collection<int, array{process: Process, candidate: array<string, mixed>}>  $created
     */
    private function suggestNames(CalendarConnection $connection, Collection $created): void
    {
        $refs = $created->values()->mapWithKeys(fn (array $item, int $index) => ['P'.($index + 1) => $item['process']]);
        $conversation = Process::query()
            ->whereBelongsTo($connection->organization)
            ->where('source', ProcessSource::Conversation)
            ->get()
            ->values()
            ->mapWithKeys(fn (Process $process, int $index) => ['C'.($index + 1) => $process]);

        try {
            $result = $this->ai->run(new NameProcessesTask(
                $created->values()->map(fn (array $item, int $index) => [
                    'ref' => 'P'.($index + 1),
                    'title' => $item['candidate']['title'],
                    'description' => $item['candidate']['description'] === null ? null : mb_substr($item['candidate']['description'], 0, 300),
                    'rhythm' => $item['candidate']['rhythm_label'],
                    'headcount' => $item['candidate']['headcount'],
                ])->all(),
                $conversation->map(fn (Process $process, string $ref) => ['ref' => $ref, 'name' => $process->name, 'rhythm' => $process->rhythm_label ?? $process->rhythm?->value])->values()->all(),
            ), AiContext::forOrganization($connection->organization, $connection));
        } catch (Throwable $exception) {
            Log::warning('Calendar naming failed', ['connection' => $connection->id, 'error' => $exception->getMessage()]);

            return;
        }

        foreach ($result->structured['suggestions'] ?? [] as $suggestion) {
            $process = $refs->get($suggestion['ref'] ?? '');

            if ($process === null) {
                continue;
            }

            $parent = $refs->get((string) ($suggestion['parent_ref'] ?? ''));

            $process->update(array_filter([
                'name' => filled($suggestion['name'] ?? null) ? mb_substr(trim($suggestion['name']), 0, 255) : null,
                'kind' => ($suggestion['kind'] ?? 'routine') === 'one_off' ? ProcessKind::OneOff : null,
                'parent_id' => $parent !== null && $parent->id !== $process->id && $parent->parent_id === null ? $parent->id : null,
            ]));

            $mergeWith = $conversation->get((string) ($suggestion['merge_with'] ?? ''));

            if ($mergeWith !== null && $process->kind === ProcessKind::Routine) {
                $mergeWith->update(['merge_candidate_id' => $process->id]);
            }
        }
    }
}
