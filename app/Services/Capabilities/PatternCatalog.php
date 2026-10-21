<?php

namespace App\Services\Capabilities;

use App\Enums\PatternType;
use App\Models\Capability;
use App\Models\Pattern;
use Illuminate\Support\Collection;

/**
 * One capability as step B of the capability measurement reads it (NM-12):
 * its card, its valid patterns as P1 …, and every detector-error pattern as a
 * prohibition X1 …, without codes, the "feltételek", the source block and the
 * case names. A capability without patterns is matched by interpretation.
 */
final class PatternCatalog
{
    /**
     * Pattern fields the matching reads, in this order.
     */
    private const FIELDS = [
        'situation' => 'Helyzet',
        'behavior' => 'Megfigyelhető viselkedés',
        'mechanism' => 'Mechanizmus',
        'minimal_trace' => 'Minimális nyom',
        'non_examples' => 'Mi nem ez',
        'alternative_explanation' => 'Alternatív magyarázat',
        'counter_test' => 'Ellenpróba',
    ];

    /**
     * @param  Collection<string, Pattern>  $patterns
     * @param  Collection<string, Pattern>  $detectors
     */
    private function __construct(
        public readonly Capability $capability,
        private Collection $patterns,
        private Collection $detectors,
        private CaseLabelScrubber $scrubber,
    ) {}

    public static function for(Capability $capability): self
    {
        $patterns = $capability->patterns()->active()->where('type', PatternType::Capability)->with('latestVersion')->orderBy('code')->get();
        $detectors = Pattern::query()->active()->where('type', PatternType::DetectorError)->orderBy('code')->get();

        return new self(
            $capability,
            $patterns->values()->mapWithKeys(fn (Pattern $pattern, int $index) => ['P'.($index + 1) => $pattern]),
            $detectors->values()->mapWithKeys(fn (Pattern $pattern, int $index) => ['X'.($index + 1) => $pattern]),
            CaseLabelScrubber::fromConfig(),
        );
    }

    public function hasPatterns(): bool
    {
        return $this->patterns->isNotEmpty();
    }

    public function pattern(string $ref): ?Pattern
    {
        return $this->patterns->get(strtoupper(trim($ref)));
    }

    public function detector(string $ref): ?Pattern
    {
        return $this->detectors->get(strtoupper(trim($ref)));
    }

    public function capabilityCard(): string
    {
        $capability = $this->capability;
        $lines = ['Definíció: '.$this->scrubber->scrub($capability->definition)];

        if (filled($capability->statement)) {
            $lines[] = 'Kimondható forma: '.$this->scrubber->scrub($capability->statement);
        }

        foreach (['non_examples' => 'Mi nem ez', 'overlaps' => 'Átfedés'] as $list => $label) {
            $items = collect($capability->{$list})->map(fn (string $item) => $this->scrubber->scrub($item))->filter();

            if ($items->isNotEmpty()) {
                $lines[] = $label.': '.$items->implode(' | ');
            }
        }

        $lines[] = 'Elsődleges szint: '.$capability->level->label();
        $lines[] = 'Csábító tétel: '.($capability->tempting ? 'igen, szinte bármire ráhúzható; csak egyértelmű nyomra illeszd' : 'nem');

        return implode("\n", $lines);
    }

    public function patternsForPrompt(): string
    {
        if ($this->patterns->isEmpty()) {
            return 'Ennek a képességnek még nincs mintája: a felismerés a definíció alapján értelmezés.';
        }

        $blocks = [];

        foreach ($this->patterns as $ref => $pattern) {
            $fit = $pattern->pivot?->fit;
            $lines = [$ref.' · '.($pattern->subtype?->label() ?? $pattern->type->label()).' · '.$this->scrubber->scrub($pattern->name)
                .($pattern->level ? ' · szint: '.$pattern->level->label() : '')
                .($fit ? ' · illeszkedés: '.__('enums.pattern_fit.'.$fit) : '')];

            foreach (self::FIELDS as $field => $label) {
                if (($text = $pattern->text($field)) !== null) {
                    $lines[] = '  '.$label.': '.$this->scrubber->scrub($text);
                }
            }

            if ($pattern->where_visible !== []) {
                $lines[] = '  Hol látszik: '.implode(', ', $pattern->where_visible);
            }

            $blocks[] = implode("\n", $lines);
        }

        return implode("\n\n", $blocks);
    }

    public function detectorsForPrompt(): string
    {
        $blocks = [];

        foreach ($this->detectors as $ref => $pattern) {
            $lines = [$ref.' · '.$this->scrubber->scrub($pattern->name)];

            foreach (['behavior' => 'Mibe csúszik bele a felismerés', 'mechanism' => 'Mechanizmus', 'counter_test' => 'Ellenpróba'] as $field => $label) {
                if (($text = $pattern->text($field)) !== null) {
                    $lines[] = '  '.$label.': '.$this->scrubber->scrub($text);
                }
            }

            $blocks[] = implode("\n", $lines);
        }

        return $blocks === [] ? 'Nincs.' : implode("\n\n", $blocks);
    }
}
