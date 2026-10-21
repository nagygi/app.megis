<?php

namespace App\Enums;

/**
 * The value of a precondition in a diagnosis (6.7): not scored.
 */
enum PreconditionValue: string
{
    case Present = 'present';
    case Partial = 'partial';
    case Absent = 'absent';
    case Unknown = 'unknown';

    public function label(): string
    {
        return __('enums.precondition_value.'.$this->value);
    }
}
