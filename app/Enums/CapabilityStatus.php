<?php

namespace App\Enums;

/**
 * How far real cases support a capability; the mégis sets it (07 · 7.2).
 */
enum CapabilityStatus: string
{
    case Used = 'used';
    case Candidate = 'candidate';
    case Literature = 'literature';
    // Merged or rejected: comes in archived (07 · 7.5).
    case SetAside = 'set_aside';

    public function label(): string
    {
        return __('enums.capability_status.'.$this->value);
    }
}
