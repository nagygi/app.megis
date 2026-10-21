<?php

namespace App\Enums;

/**
 * The four subtypes of a capability pattern (6.6). An anti-pattern looks
 * reasonable but harms the capability; a borderline case is easy to mistake for it.
 */
enum PatternSubtype: string
{
    case Presence = 'presence';
    case Absence = 'absence';
    case AntiPattern = 'anti_pattern';
    case Borderline = 'borderline';

    public function label(): string
    {
        return __('enums.pattern_subtype.'.$this->value);
    }
}
