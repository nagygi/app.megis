<?php

namespace App\Enums;

/**
 * How settled a rule of the Szabálykönyv is.
 */
enum CapabilityRuleStatus: string
{
    case Agreed = 'agreed';
    case Decided = 'decided';
    case Accepted = 'accepted';
    case Proposal = 'proposal';

    public function label(): string
    {
        return __('enums.capability_rule_status.'.$this->value);
    }
}
