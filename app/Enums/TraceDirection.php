<?php

namespace App\Enums;

/**
 * The direction of a trace: it shows the capability, its lack, or both.
 */
enum TraceDirection: string
{
    case Positive = 'positive';
    case Negative = 'negative';
    case Mixed = 'mixed';

    public function label(): string
    {
        return __('enums.trace_direction.'.$this->value);
    }
}
