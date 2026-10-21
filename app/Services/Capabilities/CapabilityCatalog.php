<?php

namespace App\Services\Capabilities;

use App\Models\Capability;
use App\Models\Precondition;
use Illuminate\Support\Collection;

/**
 * The capabilities as step A of the capability measurement reads them
 * (NM-12): neutral refs (K1 …) instead of codes and names, the definition,
 * the statement and what it is not, without the case names of the mégis tár.
 * Then E1–E9, which are no capabilities. Prefixes K, P, X and N: E is taken
 * by the preconditions.
 */
final class CapabilityCatalog
{
    /**
     * @param  Collection<string, Capability>  $byRef
     * @param  Collection<int, Precondition>  $preconditions
     */
    private function __construct(
        private Collection $byRef,
        private Collection $preconditions,
        private CaseLabelScrubber $scrubber,
    ) {}

    public static function build(): self
    {
        $capabilities = Capability::query()->active()->orderBy('code')->get();

        return new self(
            $capabilities->values()->mapWithKeys(fn (Capability $capability, int $index) => ['K'.($index + 1) => $capability]),
            Precondition::query()->orderBy('code')->get(),
            CaseLabelScrubber::fromConfig(),
        );
    }

    public function isEmpty(): bool
    {
        return $this->byRef->isEmpty();
    }

    public function resolve(string $ref): ?Capability
    {
        return $this->byRef->get(strtoupper(trim($ref)));
    }

    public function forPrompt(): string
    {
        $lines = ['Képességek:'];

        foreach ($this->byRef as $ref => $capability) {
            $lines[] = $ref.' · '.$this->scrubber->scrub($capability->definition);

            if (filled($capability->statement)) {
                $lines[] = '  Kimondható forma: '.$this->scrubber->scrub($capability->statement);
            }

            $nonExamples = collect($capability->non_examples)->take(2)->map(fn (string $item) => $this->scrubber->scrub($item))->filter();

            if ($nonExamples->isNotEmpty()) {
                $lines[] = '  Mi nem ez: '.$nonExamples->implode(' | ');
            }
        }

        $lines[] = '';
        $lines[] = 'Előfeltételek (nem képességek):';

        foreach ($this->preconditions as $precondition) {
            $lines[] = $precondition->code.' · '.$precondition->name.': '.$precondition->description;
        }

        return implode("\n", $lines);
    }
}
