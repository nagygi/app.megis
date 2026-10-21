<?php

namespace App\Enums;

/**
 * "érvényes": instead of deleting, the mégis marks a pattern obsolete or rejected.
 */
enum PatternValidity: string
{
    case Valid = 'valid';
    case Obsolete = 'obsolete';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __('enums.pattern_validity.'.$this->value);
    }
}
