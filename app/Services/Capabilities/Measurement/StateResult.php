<?php

namespace App\Services\Capabilities\Measurement;

use App\Enums\CapabilityConfidence;
use App\Enums\CapabilityState;

/**
 * The automatic state and confidence with the rules that decided them.
 * The basis holds keys and numbers only; the page words them.
 */
final readonly class StateResult
{
    /**
     * @param  list<array{rule: string, key: string, params: array<string, int|string>}>  $basis
     * @param  array{positive: int, negative: int, units: int, persons: int, pending: int}  $counts
     */
    public function __construct(
        public CapabilityState $state,
        public ?CapabilityConfidence $confidence,
        public array $basis,
        public array $counts,
    ) {}

    /**
     * @return array{basis: list<array{rule: string, key: string, params: array<string, int|string>}>, counts: array<string, int>}
     */
    public function toArray(): array
    {
        return ['basis' => $this->basis, 'counts' => $this->counts];
    }
}
