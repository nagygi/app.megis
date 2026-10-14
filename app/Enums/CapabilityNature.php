<?php

namespace App\Enums;

/**
 * Jelleg (07 · 7.2): a skill-like item may later move into the people side of a capability.
 */
enum CapabilityNature: string
{
    case Capability = 'capability';
    case SkillLike = 'skill_like';
    case Questionable = 'questionable';

    public function label(): string
    {
        return __('enums.capability_nature.'.$this->value);
    }
}
