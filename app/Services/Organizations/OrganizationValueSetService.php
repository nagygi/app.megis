<?php

namespace App\Services\Organizations;

use App\Enums\ValueOrigin;
use App\Models\Organization;
use App\Models\OrganizationValueSet;
use App\Models\Value;
use App\Models\Version;
use App\Services\Values\ValueService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Saves an organization's value set (08 · 8.1, 8.2, 8.4). A value that is not
 * in the Értéktár yet goes there as "Szervezet által felvett" (03 · 3.7).
 */
class OrganizationValueSetService
{
    public function __construct(private ValueService $values) {}

    public function forOrganization(Organization $organization): OrganizationValueSet
    {
        return OrganizationValueSet::query()->firstOrCreate(['organization_id' => $organization->id]);
    }

    /**
     * Prefilled card of an Értéktár value: the first, most important item of each
     * list, from the current version (08 · 8.1, 03 · 3.2).
     *
     * @return array<string, mixed>
     */
    public function itemFromValue(Value $value): array
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
        ];
    }

    /**
     * Replaces the values and the brand base; returns the new version, or null
     * when nothing changed. The customer side saves the working copy on every
     * change and records the version when the step is left (plan 3.5).
     *
     * @param  list<array<string, mixed>>  $items  value_id null means a new value by its name
     * @param  array<string, ?string>  $brand  purpose, value_proposition, impact
     */
    public function save(OrganizationValueSet $set, array $items, array $brand, bool $recordVersion = true): ?Version
    {
        return DB::transaction(function () use ($set, $items, $brand, $recordVersion): ?Version {
            $keys = [];

            foreach (array_values($items) as $position => $item) {
                $valueId = $item['value_id'] ?? null;
                $sourceVersionId = $item['source_version_id'] ?? null;

                if ($valueId === null) {
                    $value = $this->values->createElsewhere($item['name'], ValueOrigin::Organization, $set->organization, [
                        'meanings' => $this->asList($item['meaning'] ?? null),
                        'non_meanings' => $this->asList($item['non_meaning'] ?? null),
                        'low_examples' => $this->asList($item['low_example'] ?? null),
                        'high_examples' => $this->asList($item['high_example'] ?? null),
                        'examples' => $this->asList($item['example'] ?? null),
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
                    'meaning' => $this->text($item['meaning'] ?? null),
                    'non_meaning' => $this->text($item['non_meaning'] ?? null),
                    'low_example' => (string) $this->text($item['low_example'] ?? null),
                    'high_example' => (string) $this->text($item['high_example'] ?? null),
                    'example' => $this->text($item['example'] ?? null),
                    'position' => $position,
                ]);
            }

            $set->items()->whereNotIn('key', $keys)->delete();

            $set->fill(collect(OrganizationValueSet::BRAND_FIELDS)
                ->mapWithKeys(fn (string $field) => [$field => $this->text($brand[$field] ?? null)])
                ->all());
            $set->save();

            return $recordVersion ? $set->recordVersion() : null;
        });
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
