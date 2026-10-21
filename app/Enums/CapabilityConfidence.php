<?php

namespace App\Enums;

/**
 * How sure the state is (6.3, KO-01). The machine gives at most medium;
 * high only a person. Disputed: contradicting traces, at most medium.
 */
enum CapabilityConfidence: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Disputed = 'disputed';

    public function label(): string
    {
        return __('enums.capability_confidence.'.$this->value);
    }
}
