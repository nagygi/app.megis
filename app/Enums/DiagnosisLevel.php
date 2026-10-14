<?php

namespace App\Enums;

/**
 * Who a diagnosis is about (04 · 4.3, 13 · 13.1).
 */
enum DiagnosisLevel: string
{
    case Organization = 'organization';
    case Team = 'team';
    case User = 'user';
    // One process of the organization (13 · 13.1).
    case Process = 'process';

    public function label(): string
    {
        return __('enums.diagnosis_level.'.$this->value);
    }

    /**
     * Team and organization data can be loaded from the users' diagnoses (04 · 4.5).
     */
    public function canAggregate(): bool
    {
        return in_array($this, [self::Organization, self::Team], true);
    }
}
