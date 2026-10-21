<?php

namespace App\Enums;

/**
 * The areas of the Szabálykönyv, by the prefix of the rule code.
 */
enum CapabilityRuleArea: string
{
    case Trace = 'trace';
    case State = 'state';
    case Confidence = 'confidence';
    case Maturity = 'maturity';
    case Priority = 'priority';
    case Protection = 'protection';
    case Precondition = 'precondition';
    case Development = 'development';

    public function label(): string
    {
        return __('enums.capability_rule_area.'.$this->value);
    }
}
