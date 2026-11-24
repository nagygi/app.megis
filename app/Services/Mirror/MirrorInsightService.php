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
 * the user's own words, from their quotes. The user approves each value, or
 * writes any part of it, the name too; it goes into the own values with the
 * account (24 · V2).
 */
class MirrorInsightService
{
    public const MEANING_MAX = 500;

    public const NAME_MAX = 80;

    public const SIGN_MAX = 120;

    public const SIGNS_MAX = 4;

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
     * too: they can come back. What the user wrote, or approved with the
     * Coach's words, stays when the Coach runs again ("Újra").
     */
    public function deepen(MirrorSession $session, AiContext $context): void
    {
        $groups = $this->mirrors->groups($session)->values();
        $kept = $session->insights()->get()
            ->filter(fn (MirrorValueInsight $insight) => $insight->isEdited() || ($insight->isAccepted() && filled($insight->meaning)))
            ->pluck('group_key')
            ->all();
        $session->insights()->whereNull('accepted_at')->whereNull('edited_at')->delete();

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

            if ($group === null || in_array($group->key, $kept, true) || blank($row['meaning'] ?? null)) {
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
     * "Jóváhagyom": the user looked the value through and keeps it as it
     * stands, the Coach's words or the Értéktár's (06 · ET3, ET4).
     */
    public function approve(MirrorSession $session, string $key): void
    {
        $insight = $session->insights()->firstOrNew(['group_key' => $key]);
        $insight->accepted_at = now();
        $insight->save();
    }

    /**
     * "Átírom": the user's own words for any part of the value, the name too.
     * It is their own definition from then on, not the Értéktár's; the
     * Értéktár does not change. Written, it is approved too.
     *
     * @param  array{name: string, meaning: string, signs: list<string>, low: ?string, high: ?string}  $fields
     */
    public function edit(MirrorSession $session, MirrorValueGroup $group, array $fields): void
    {
        $detectedName = $group->originalName ?? $group->name;
        $name = trim($fields['name']);

        $session->insights()->updateOrCreate(['group_key' => $group->key], [
            'name' => $name === '' || mb_strtolower($name) === mb_strtolower($detectedName) ? null : mb_substr($name, 0, self::NAME_MAX),
            'meaning' => $this->clip($fields['meaning']),
            'signs' => collect($fields['signs'])->map(fn (string $sign) => mb_substr(trim($sign), 0, self::SIGN_MAX))->filter()->take(self::SIGNS_MAX)->values()->all(),
            'low_example' => filled($fields['low']) ? $this->clip($fields['low']) : null,
            'high_example' => filled($fields['high']) ? $this->clip($fields['high']) : null,
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
