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

    /** Built on first use; empty when no value has an English name. */
    private ?string $namePattern = null;

    /** @var array<string, string> English value name => what the prompt says instead */
    private array $nameReplacements = [];

    /**
     * @param  Collection<int, Value>  $values  with latestVersion loaded
     * @param  array<int, array<string, list<string>>>  $definitions  value id => the organization's own words, by label
     */
    public function __construct(Collection $values, private array $definitions = [])
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

    /**
     * For the materials of a main diagnosis (13 · 13.2, 18 · 18.4): the
     * organization's values in its own words from the recorded value set
     * version, next to the rest of the Értéktár.
     *
     * @param  array<string, mixed>  $valueSetSnapshot
     */
    public static function forDiagnosis(Organization $organization, array $valueSetSnapshot): self
    {
        $definitions = [];

        foreach ($valueSetSnapshot['values'] ?? [] as $item) {
            $definitions[(int) $item['value_id']] = array_filter([
                'Mit jelent' => array_values(array_filter([$item['meaning'] ?? null])),
                'Mit nem jelent' => array_values(array_filter([$item['non_meaning'] ?? null])),
                '6 · Mikor teljesül nagyon' => array_values(array_filter([$item['high_example'] ?? null])),
                '1 · Mikor nagyon nem teljesül' => array_values(array_filter([$item['low_example'] ?? null])),
                'Példák' => array_values(array_filter([$item['example'] ?? null])),
            ]);
        }

        $values = Value::query()
            ->where(fn ($query) => $query->availableTo($organization)->orWhereIn('id', array_keys($definitions)))
            ->with('latestVersion')
            ->orderBy('id')
            ->get();

        return new self($values, $definitions);
    }

    /**
     * The values every material of a diagnosis is scored on (13 · 13.2).
     *
     * @return list<int>
     */
    public function requiredValueIds(): array
    {
        return array_values(array_filter(array_keys($this->definitions), fn (int $valueId) => isset($this->refByValueId[$valueId])));
    }

    public function isEmpty(): bool
    {
        return $this->byRef === [];
    }

    public function resolve(string $ref): ?Value
    {
        // "[E7]" too: the boundaries name the other values by their code in brackets.
        return $this->byRef[strtoupper(trim($ref, " \t\n\r[]"))] ?? null;
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

    /**
     * The organization's own words replace the definition and the content
     * lists; the boundaries against the other values and the synonyms go
     * along either way. Literature, ratings, code and names never do.
     */
    private function describe(string $ref): string
    {
        $value = $this->byRef[$ref];
        $lines = ['['.$ref.']'];

        $boundaries = [
            'Hamis pozitívok (hasonlít, de nem ez)' => $value->false_positives,
            'Átfedések más értékekkel' => $value->overlaps,
        ];

        $sections = isset($this->definitions[$value->id])
            ? [...$this->definitions[$value->id], ...$boundaries]
            : [
                'Definíció' => array_values(array_filter([$value->definition])),
                'Mit jelent' => $value->meanings,
                'Mit nem jelent' => $value->non_meanings,
                ...$boundaries,
                '6 · Mikor teljesül nagyon' => $value->high_examples,
                '1 · Mikor nagyon nem teljesül' => $value->low_examples,
                'Példák' => $value->examples,
            ];

        $sections['Jellemző kifejezések'] = $value->keywords;

        foreach ($sections as $label => $texts) {
            if ($texts !== []) {
                $lines[] = $label.': '.implode(' | ', array_map(fn (string $text): string => $this->withoutNames($text), $texts));
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Other values come up by their English name in the boundaries ("Creativity
     * (SDT)"): a catalog value becomes its code, any other "egy másik érték",
     * so the engine still never matches on a name.
     */
    private function withoutNames(string $text): string
    {
        if ($this->namePattern === null) {
            $this->namePattern = '';
            $this->nameReplacements = [];

            foreach (Value::query()->whereNotNull('name_en')->pluck('name_en', 'id') as $valueId => $name) {
                $name = trim((string) $name);

                if ($name !== '') {
                    $this->nameReplacements[$name] = isset($this->refByValueId[$valueId]) ? '['.$this->refByValueId[$valueId].']' : 'egy másik érték';
                }
            }

            if ($this->nameReplacements !== []) {
                $names = array_keys($this->nameReplacements);
                // The longest first, so "Self-respect" wins over a shorter name inside it.
                usort($names, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
                $this->namePattern = '/(?<![\p{L}\p{N}])(?:'.implode('|', array_map(fn (string $name): string => preg_quote($name, '/'), $names)).')(?![\p{L}\p{N}])/u';
            }
        }

        if ($this->namePattern === '') {
            return $text;
        }

        return preg_replace_callback($this->namePattern, fn (array $match): string => $this->nameReplacements[$match[0]], $text) ?? $text;
    }
}
