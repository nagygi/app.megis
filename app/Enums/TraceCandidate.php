<?php

namespace App\Enums;

/**
 * A trace no pattern fits: a new pattern candidate, or, when no capability
 * fits either, a missing capability candidate. Only the mégis adds patterns (NY-26).
 */
enum TraceCandidate: string
{
    case NewPattern = 'new_pattern';
    case MissingCapability = 'missing_capability';

    public function label(): string
    {
        return __('enums.trace_candidate.'.$this->value);
    }
}
