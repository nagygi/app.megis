<?php

namespace App\Services\Engine;

use App\Models\Organization;
use App\Models\Value;
use Illuminate\Support\Collection;

/**
 * The Értéktár values the engine may match to, under neutral codes (E1, E2, …).
 * Step B sees definitions and examples only, never names: a match must come
 * from meaning, not from a similar word (06 · 6.4). Synonyms go along as
 * "jellemző kifejezések" (plan, open question 4).
 */
final class ValueCatalog
{
    /** @var array<string, Value> */
    private array $byRef = [];

    /** @var array<int, string> */
    private array $refByValueId = [];

    /**
     * @param  Collection<int, Value>  $values  with latestVersion loaded
     */
    public function __construct(Collection $values)
    {
        foreach ($values->values() as $index => $value) {
            $ref = 'E'.($index + 1);
            $this->byRef[$ref] = $value;
            $this->refByValueId[$value->id] = $ref;
        }
    }

    /**
     * The values selectable in the organization; reviewed ones only without one (03 · 3.7).
     */
    public static function forOrganization(?Organization $organization): self
    {
        return new self(Value::query()->availableTo($organization)->with('latestVersion')->orderBy('id')->get());
    }

    public function isEmpty(): bool
    {
        return $this->byRef === [];
    }

    public function resolve(string $ref): ?Value
    {
        return $this->byRef[strtoupper(trim($ref))] ?? null;
    }

    public function refFor(int $valueId): ?string
    {
        return $this->refByValueId[$valueId] ?? null;
    }

    /**
     * The whole catalog for the matching prompt, without names.
     */
    public function forPrompt(): string
    {
        return implode("\n\n", array_map(
            fn (string $ref): string => $this->describe($ref),
            array_keys($this->byRef),
        ));
    }

    /**
     * Selected values for the scoring prompt, with both scale ends.
     *
     * @param  list<int>  $valueIds
     */
    public function forScoring(array $valueIds): string
    {
        return implode("\n\n", array_filter(array_map(
            fn (int $valueId): ?string => isset($this->refByValueId[$valueId]) ? $this->describe($this->refByValueId[$valueId]) : null,
            $valueIds,
        )));
    }

    private function describe(string $ref): string
    {
        $value = $this->byRef[$ref];
        $lines = ['['.$ref.']'];

        foreach ([
            'meanings' => 'Mit jelent',
            'non_meanings' => 'Mit nem jelent',
            'high_examples' => '6 · Mikor teljesül nagyon',
            'low_examples' => '1 · Mikor nagyon nem teljesül',
            'examples' => 'Példák',
            'keywords' => 'Jellemző kifejezések',
        ] as $field => $label) {
            if ($value->{$field} !== []) {
                $lines[] = $label.': '.implode(' | ', $value->{$field});
            }
        }

        return implode("\n", $lines);
    }
}
