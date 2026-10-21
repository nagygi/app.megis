<?php

namespace App\Services\Capabilities\Measurement;

use App\Enums\FindingKind;
use App\Enums\FindingStatus;
use App\Enums\PatternSubtype;

/**
 * A finding as the state calculation sees it: the subtype of its pattern
 * (an interpretation has none), its status and its traces.
 */
final readonly class FindingInput
{
    /**
     * @param  list<TraceInput>  $traces
     */
    public function __construct(
        public FindingKind $kind,
        public ?PatternSubtype $subtype,
        public FindingStatus $status,
        public array $traces,
        public bool $needsFirstApproval = false,
    ) {}
}
