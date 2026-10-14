<?php

namespace App\Enums;

/**
 * Who a diagnosis is about (04 · 4.3). The process level comes with 11.
 */
enum DiagnosisLevel: string
{
    case Organization = 'organization';
    case Team = 'team';
    case User = 'user';

    public function label(): string
    {
        return __('enums.diagnosis_level.'.$this->value);
    }

    /**
     * Team and organization data can be loaded from the users' diagnoses (04 · 4.5).
     */
    public function canAggregate(): bool
    {
        return $this !== self::User;
    }
}
