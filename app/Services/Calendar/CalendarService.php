<?php

namespace App\Services\Calendar;

use App\Enums\MainDiagnosisStage;
use App\Models\CalendarConnection;
use App\Models\MainDiagnosis;
use App\Models\Organization;
use App\Models\ProcessOccasion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Connecting, refreshing and disconnecting a calendar (09). The last 90 days
 * are read; the metadata lives 90 days from connecting and goes at once on
 * disconnecting. Processes and the dates of their occasions stay (09 · 9.5).
 */
class CalendarService
{
    public const WINDOW_DAYS = 90;

    public function __construct(
        private IcsParser $ics,
        private OAuthCalendarSource $oauth,
        private CalendarDetector $detector,
    ) {}

    /**
     * The secret iCal address (09 · 9.2), fetched now and every day. An admin
     * may load it in the leader's name at the leader's request ($connectedBy).
     */
    public function connectIcsUrl(User $user, Organization $organization, ?MainDiagnosis $main, string $url, ?User $connectedBy = null): CalendarConnection
    {
        $events = $this->ics->parse($this->download($url), ...$this->window(), ownerEmail: $user->email);

        return $this->connect($user, $organization, $main, CalendarConnection::ICS_URL, ['url' => $url], $events, $connectedBy);
    }

    /**
     * A one-time export: no refresh; a new file refreshes it (09 · 9.2). An
     * admin may load it in the leader's name at the leader's request ($connectedBy).
     */
    public function connectIcsFile(User $user, Organization $organization, ?MainDiagnosis $main, string $contents, ?User $connectedBy = null): CalendarConnection
    {
        $events = $this->ics->parse($contents, ...$this->window(), ownerEmail: $user->email);

        return $this->connect($user, $organization, $main, CalendarConnection::ICS_FILE, null, $events, $connectedBy);
    }

    /**
     * Google or Microsoft after the consent screen (09 · 9.2).
     *
     * @param  array{access_token: string, refresh_token: ?string, expires_at: int}  $tokens
     */
    public function connectOAuth(User $user, Organization $organization, ?MainDiagnosis $main, string $provider, array $tokens): CalendarConnection
    {
        $connection = $this->newConnection($user, $organization, $main, $provider, $tokens);

        try {
            $events = $this->oauth->events($connection, ...$this->window());
        } catch (CalendarException $exception) {
            $connection->delete();

            throw $exception;
        }

        $this->import($connection, $events);
        $this->detector->detect($connection);

        return $connection->refresh();
    }

    /**
     * The daily refresh until the time window ends (09 · 9.4); at the end the
     * calendar is disconnected (09 · 9.5). A lost access breaks the connection (09 · 9.6).
     */
    public function sync(CalendarConnection $connection): void
    {
        if (! $connection->isActive() || ! $connection->refreshes()) {
            return;
        }

        if ($this->windowEnded($connection)) {
            $this->disconnect($connection);

            return;
        }

        try {
            $events = $connection->provider === CalendarConnection::ICS_URL
                ? $this->ics->parse($this->download((string) ($connection->credentials['url'] ?? '')), ...$this->window(), ownerEmail: $connection->user->email)
                : $this->oauth->events($connection, ...$this->window());
        } catch (CalendarException $exception) {
            $connection->update([
                'status' => $exception->accessLost ? CalendarConnection::BROKEN : $connection->status,
                'error' => $exception->getMessage(),
            ]);
            Log::warning('Calendar sync failed', ['connection' => $connection->id, 'access_lost' => $exception->accessLost]);

            return;
        }

        $this->import($connection, $events);
        $this->detector->addNewOccasions($connection);
        $connection->update(['last_synced_at' => now(), 'error' => null]);
    }

    /**
     * "Szétkötés": the access ends and the metadata goes at once; processes
     * and occasion dates stay (09 · 9.5).
     */
    public function disconnect(CalendarConnection $connection): void
    {
        DB::transaction(function () use ($connection): void {
            $this->deleteMetadata($connection);
            $connection->update([
                'status' => CalendarConnection::DISCONNECTED,
                'credentials' => null,
                'disconnected_at' => now(),
            ]);
        });
    }

    /**
     * Metadata older than 90 days from connecting goes (09 · 9.5, cron.md).
     */
    public function pruneExpired(): int
    {
        $count = 0;

        CalendarConnection::query()
            ->where('metadata_expires_at', '<=', now())
            ->whereHas('events')
            ->each(function (CalendarConnection $connection) use (&$count): void {
                $this->deleteMetadata($connection);
                $count++;
            });

        return $count;
    }

    /**
     * @param  array<string, mixed>|null  $credentials
     * @param  list<CalendarEventData>  $events
     */
    private function connect(User $user, Organization $organization, ?MainDiagnosis $main, string $provider, ?array $credentials, array $events, ?User $connectedBy): CalendarConnection
    {
        $connection = $this->newConnection($user, $organization, $main, $provider, $credentials, $connectedBy);
        $this->import($connection, $events);
        $this->detector->detect($connection);

        return $connection->refresh();
    }

    /**
     * A new connection replaces the user's previous one in the organization (09 · 9.5).
     *
     * @param  array<string, mixed>|null  $credentials
     */
    private function newConnection(User $user, Organization $organization, ?MainDiagnosis $main, string $provider, ?array $credentials, ?User $connectedBy = null): CalendarConnection
    {
        CalendarConnection::query()
            ->whereBelongsTo($user)
            ->whereBelongsTo($organization)
            ->where('status', '!=', CalendarConnection::DISCONNECTED)
            ->get()
            ->each(fn (CalendarConnection $previous) => $this->disconnect($previous));

        return CalendarConnection::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'main_diagnosis_id' => $main?->id,
            'connected_by_id' => $connectedBy?->id,
            'provider' => $provider,
            'credentials' => $credentials,
            'status' => CalendarConnection::ACTIVE,
            'connected_at' => now(),
            'last_synced_at' => now(),
            'metadata_expires_at' => now()->addDays(self::WINDOW_DAYS),
        ]);
    }

    /**
     * @param  list<CalendarEventData>  $events
     */
    private function import(CalendarConnection $connection, array $events): void
    {
        // Past the metadata's life nothing is stored any more (09 · 9.5).
        if ($connection->metadata_expires_at->isPast()) {
            return;
        }

        foreach ($events as $event) {
            $connection->events()->updateOrCreate(['external_id' => $event->externalId], [
                'series_key' => $event->seriesKey,
                'title' => $event->title,
                'description' => $event->description,
                'starts_at' => $event->startsAt,
                'ends_at' => $event->endsAt,
                'attendees' => $event->attendees,
            ]);
        }
    }

    private function deleteMetadata(CalendarConnection $connection): void
    {
        $eventIds = $connection->events()->pluck('id');

        ProcessOccasion::query()->whereIn('calendar_event_id', $eventIds)->update(['calendar_event_id' => null]);
        $connection->events()->delete();
    }

    /**
     * The diagnosis' time window ended: publishing day passed or it is no longer running.
     */
    private function windowEnded(CalendarConnection $connection): bool
    {
        $main = $connection->mainDiagnosis;

        return $main !== null && ($main->publishes_on->isPast() || in_array($main->stage, [MainDiagnosisStage::Published, MainDiagnosisStage::Closed], true));
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function window(): array
    {
        $now = CarbonImmutable::now();

        return [$now->subDays(self::WINDOW_DAYS), $now];
    }

    private function download(string $url): string
    {
        if (! str_starts_with($url, 'https://') && ! str_starts_with($url, 'webcal://')) {
            throw new CalendarException(__('calendar.url_invalid'));
        }

        try {
            $response = Http::timeout(30)->withHeaders(['Accept' => 'text/calendar'])->get(preg_replace('/^webcal:/', 'https:', $url));
        } catch (ConnectionException) {
            throw new CalendarException(__('calendar.failed'));
        }

        if (! $response->successful()) {
            throw new CalendarException(__('calendar.failed'), accessLost: in_array($response->status(), [401, 403, 404], true));
        }

        return $response->body();
    }
}
