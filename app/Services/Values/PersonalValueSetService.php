<?php

namespace App\Services\Values;

use App\Enums\MaterialContext;
use App\Enums\MaterialStatus;
use App\Enums\MirrorStatus;
use App\Models\Material;
use App\Models\MirrorSession;
use App\Models\Organization;
use App\Models\PersonalValueSet;
use App\Models\PersonalValueSetItem;
use App\Models\RawDetection;
use App\Models\User;
use App\Models\Value;
use App\Models\Version;
use App\Services\Mirror\MirrorService;
use App\Services\Organizations\OrganizationValueSetService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A user's own values (24): the working copy saved on every change, a
 * version when the user leaves the step, the values brought from the
 * Értéktükör (24.5) and their state from the user's own materials (24.4).
 */
class PersonalValueSetService
{
    public const STATE_STRONG = 'strong';

    public const STATE_PARTIAL = 'partial';

    public const STATE_NONE = 'none';

    public function __construct(
        private ValueService $values,
        private MirrorService $mirrors,
        private OrganizationValueSetService $organizationValueSets,
    ) {}

    public function forUser(User $user): PersonalValueSet
    {
        return PersonalValueSet::query()->firstOrCreate(['user_id' => $user->id]);
    }

    /**
     * The card of an Értéktár value, from its current version (24 · 24.3).
     *
     * @return array<string, mixed>
     */
    public function itemFromValue(Value $value, string $source = PersonalValueSetItem::SOURCE_LIBRARY): array
    {
        return [
            'key' => (string) Str::uuid(),
            'value_id' => $value->id,
            'source_version_id' => $value->latestVersion?->id,
            'name' => $value->name,
            'meaning' => $value->meanings[0] ?? '',
            'non_meaning' => $value->non_meanings[0] ?? '',
            'low_example' => $value->low_examples[0] ?? '',
            'high_example' => $value->high_examples[0] ?? '',
            'example' => $value->examples[0] ?? '',
            'source' => $source,
            'quotes' => null,
        ];
    }

    /**
     * Replaces the list. A value without an Értéktár id is created there as
     * "Felhasználó által felvett" (24, question 3). Returns the version when
     * one is recorded and the content changed.
     *
     * @param  list<array<string, mixed>>  $items
     */
    public function save(PersonalValueSet $set, array $items, bool $recordVersion): ?Version
    {
        return DB::transaction(function () use ($set, $items, $recordVersion): ?Version {
            $keys = [];

            foreach (array_values($items) as $position => $item) {
                $valueId = $item['value_id'] ?? null;
                $sourceVersionId = $item['source_version_id'] ?? null;

                if ($valueId === null) {
                    $value = $this->values->createForUser($item['name'], $set->user, [
                        'meanings' => $this->asList($item['meaning'] ?? null),
                        'non_meanings' => $this->asList($item['non_meaning'] ?? null),
                        'low_examples' => $this->asList($item['low_example'] ?? null),
                        'high_examples' => $this->asList($item['high_example'] ?? null),
                    ]);
                    $valueId = $value->id;
                    $sourceVersionId = $value->latestVersion?->id;
                }

                $key = $item['key'] ?? (string) Str::uuid();
                $keys[] = $key;

                $set->items()->updateOrCreate(['key' => $key], [
                    'value_id' => $valueId,
                    'source_version_id' => $sourceVersionId,
                    'name' => Value::query()->whereKey($valueId)->value('name') ?? $item['name'],
                    'meaning' => (string) $this->text($item['meaning'] ?? null),
                    'non_meaning' => $this->text($item['non_meaning'] ?? null),
                    'low_example' => $this->text($item['low_example'] ?? null),
                    'high_example' => $this->text($item['high_example'] ?? null),
                    'example' => $this->text($item['example'] ?? null),
                    'source' => $item['source'] ?? PersonalValueSetItem::SOURCE_NEW,
                    'quotes' => $item['quotes'] ?? null,
                    'position' => $position,
                ]);
            }

            $set->items()->whereNotIn('key', $keys)->delete();
            $set->touch();

            return $recordVersion ? $set->recordVersion() : null;
        });
    }

    /**
     * A private person's own values are the organization's values too (08):
     * the organization's list follows the own list, its brand base stays.
     */
    public function syncOrganization(PersonalValueSet $set, Organization $organization): ?Version
    {
        $valueSet = $this->organizationValueSets->forOrganization($organization);

        return $this->organizationValueSets->save(
            $valueSet,
            $set->items()->get()->map(fn (PersonalValueSetItem $item) => [
                'key' => $item->key,
                'value_id' => $item->value_id,
                'source_version_id' => $item->source_version_id,
                'name' => $item->name,
                'meaning' => $item->meaning,
                'non_meaning' => $item->non_meaning,
                'low_example' => $item->low_example,
                'high_example' => $item->high_example,
                'example' => $item->example,
            ])->all(),
            [
                'purpose' => $valueSet->purpose,
                'value_proposition' => $valueSet->value_proposition,
                'impact' => $valueSet->impact,
            ],
        );
    }

    /**
     * The user's latest Értéktükör tied to the account (24, question 4).
     */
    public function latestMirror(User $user): ?MirrorSession
    {
        return MirrorSession::query()
            ->where('user_id', $user->id)
            ->whereNotNull('claimed_at')
            ->whereIn('status', [MirrorStatus::Ready, MirrorStatus::Claimed])
            ->whereHas('material', fn ($query) => $query->where('status', MaterialStatus::Processed))
            ->latest('id')
            ->first();
    }

    /**
     * The values of an Értéktükör with their quotes, "nem az enyém" left out
     * (24 · 24.5). A matched value comes as the Értéktár value, an unmatched
     * one as the "AI által detektált" value made of it.
     *
     * @param  list<int>  $existingValueIds
     * @return list<array<string, mixed>>
     */
    public function itemsFromMirror(MirrorSession $session, array $existingValueIds, int $limit): array
    {
        $items = [];

        foreach ($this->mirrors->groups($session) as $group) {
            if ($group->excluded || count($items) >= $limit) {
                continue;
            }

            $valueId = $group->valueId ?? RawDetection::query()->whereIn('id', $group->detectionIds)->whereNotNull('unmatched_value_id')->value('unmatched_value_id');
            $value = $valueId === null ? null : Value::query()->with('latestVersion')->find($valueId);

            if ($value === null || in_array($value->id, $existingValueIds, true)) {
                continue;
            }

            $existingValueIds[] = $value->id;
            $items[] = [
                ...$this->itemFromValue($value, PersonalValueSetItem::SOURCE_MIRROR),
                'example' => $group->quotes[0] ?? ($value->examples[0] ?? ''),
                'quotes' => $group->quotes,
            ];
        }

        return $items;
    }

    /**
     * "erősen jelen" in at least two thirds of the user's own materials,
     * "részben" in at least one, otherwise "még nincs beépítve" (24 · 24.4).
     * Only a match of step B counts, never a name (06 · 6.4).
     *
     * @param  list<int>  $valueIds
     * @return array{total: int, counts: array<int, int>}
     */
    public function occurrences(User $user, array $valueIds): array
    {
        $materialIds = Material::query()
            ->where('user_id', $user->id)
            ->where('context', MaterialContext::Mirror)
            ->where('status', MaterialStatus::Processed)
            ->pluck('id');

        if ($materialIds->isEmpty() || $valueIds === []) {
            return ['total' => $materialIds->count(), 'counts' => []];
        }

        $counts = RawDetection::query()
            ->whereIn('raw_detections.material_id', $materialIds)
            ->whereNull('raw_detections.excluded_at')
            ->join('detection_matches', 'detection_matches.raw_detection_id', '=', 'raw_detections.id')
            ->where('detection_matches.confidence', '>=', (float) config('megis.engine.match_threshold'))
            ->whereIn('detection_matches.value_id', $valueIds)
            ->selectRaw('detection_matches.value_id as value_id, count(distinct raw_detections.material_id) as materials')
            ->groupBy('detection_matches.value_id')
            ->pluck('materials', 'value_id')
            ->map(fn ($count) => (int) $count)
            ->all();

        return ['total' => $materialIds->count(), 'counts' => $counts];
    }

    public function state(int $count, int $total): string
    {
        return match (true) {
            $total > 0 && $count * 3 >= $total * 2 => self::STATE_STRONG,
            $count > 0 => self::STATE_PARTIAL,
            default => self::STATE_NONE,
        };
    }

    /**
     * @return Collection<int, Value>
     */
    public function libraryFor(User $user, ?Organization $organization, string $search, array $exceptIds): Collection
    {
        return Value::query()
            ->availableToUser($user, $organization)
            ->whereNotIn('id', $exceptIds)
            ->when(trim($search) !== '', fn ($query) => $query->whereLike('search_text', '%'.mb_strtolower(trim($search)).'%'))
            ->orderBy('name')
            ->limit(20)
            ->get();
    }

    private function text(?string $text): ?string
    {
        $text = trim((string) $text);

        return $text === '' ? null : $text;
    }

    /**
     * @return list<string>
     */
    private function asList(?string $text): array
    {
        $text = $this->text($text);

        return $text === null ? [] : [$text];
    }
}
