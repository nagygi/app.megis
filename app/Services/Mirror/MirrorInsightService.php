<?php

namespace App\Services\Mirror;

use App\Ai\AiContext;
use App\Ai\Tasks\DeepenMirrorValuesTask;
use App\Enums\MirrorInsightStatus;
use App\Jobs\DeepenMirrorValues;
use App\Models\MirrorSession;
use App\Models\MirrorValueInsight;
use App\Models\RawDetection;
use App\Models\Value;
use App\Services\Ai\AiGateway;
use Illuminate\Support\Collection;

/**
 * ET4 · elmélyülés (NM-06): the mégis Coach puts each value of a mirror into
 * the user's own words, from their quotes. The user takes the meaning or
 * rewrites it; it goes into the own values with the account (24 · V2).
 */
class MirrorInsightService
{
    public const MEANING_MAX = 500;

    public function __construct(
        private MirrorService $mirrors,
        private AiGateway $ai,
    ) {}

    /**
     * After the measurement, in a job of its own: ET3 does not wait for it.
     */
    public function start(MirrorSession $session): void
    {
        $session->update(['insight_status' => MirrorInsightStatus::Pending]);

        DeepenMirrorValues::dispatch($session);
    }

    /**
     * One call for every value of the mirror, the ones marked "nem az enyém"
     * too: they can come back.
     */
    public function deepen(MirrorSession $session, AiContext $context): void
    {
        $groups = $this->mirrors->groups($session)->values();
        $session->insights()->delete();

        if ($groups->isEmpty()) {
            $session->update(['insight_status' => MirrorInsightStatus::Ready]);

            return;
        }

        $refs = $groups->mapWithKeys(fn (MirrorValueGroup $group, int $index) => ['V'.($index + 1) => $group]);
        $values = Value::query()->whereIn('id', $groups->pluck('valueId')->filter())->get()->keyBy('id');
        $reasons = RawDetection::query()->whereIn('id', $groups->pluck('detectionIds')->flatten())->pluck('reasoning', 'id');

        $result = $this->ai->run(new DeepenMirrorValuesTask($refs->map(fn (MirrorValueGroup $group, string $ref): array => [
            'ref' => $ref,
            'name' => $group->name,
            'quotes' => $group->quotes,
            'reasoning' => collect($group->detectionIds)->map(fn (int $id) => $reasons[$id] ?? null)->filter()->unique()->values()->all(),
            'library' => $this->libraryText($values->get($group->valueId)),
        ])->values()->all()), $context);

        foreach ($result->structured['values'] ?? [] as $row) {
            $group = $refs->get($row['ref'] ?? '');

            if ($group === null || blank($row['meaning'] ?? null)) {
                continue;
            }

            $session->insights()->updateOrCreate(['group_key' => $group->key], [
                'meaning' => $this->clip($row['meaning']),
                'signs' => collect($row['signs'] ?? [])->map(fn ($sign) => trim((string) $sign))->filter()->take(4)->values()->all(),
                'low_example' => filled($row['low'] ?? null) ? $this->clip($row['low']) : null,
                'high_example' => filled($row['high'] ?? null) ? $this->clip($row['high']) : null,
                'ai_call_id' => $result->call->id,
            ]);
        }

        $session->update(['insight_status' => MirrorInsightStatus::Ready]);
    }

    /**
     * @return Collection<string, MirrorValueInsight> by group key
     */
    public function forSession(MirrorSession $session): Collection
    {
        return $session->insights()->get()->keyBy('group_key');
    }

    /**
     * "Ez az": the suggestion is the user's meaning now.
     */
    public function accept(MirrorSession $session, string $key): void
    {
        $session->insights()->where('group_key', $key)->first()?->update(['accepted_at' => now()]);
    }

    /**
     * "Átírom": the user's own sentence instead of the suggestion.
     */
    public function rewrite(MirrorSession $session, string $key, string $meaning): void
    {
        $session->insights()->where('group_key', $key)->first()?->update([
            'meaning' => $this->clip($meaning),
            'edited_at' => now(),
            'accepted_at' => now(),
        ]);
    }

    /**
     * The Értéktár's general words, as a direction only.
     */
    private function libraryText(?Value $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = implode(' ', array_filter([$value->definition, ...array_slice($value->meanings, 0, 2)]));

        return $text === '' ? null : $text;
    }

    private function clip(string $text): string
    {
        return mb_substr(trim($text), 0, self::MEANING_MAX);
    }
}
