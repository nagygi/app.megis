<?php

namespace App\Enums;

/**
 * How a diagnosis came about, set by the system (04 · 4.3).
 */
enum DiagnosisCreator: string
{
    case Admin = 'admin';
    case Aggregated = 'aggregated';
    case Automatic = 'automatic';

    public function label(): string
    {
        return __('enums.diagnosis_creator.'.$this->value);
    }
}
