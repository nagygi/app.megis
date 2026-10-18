<?php

namespace App\Enums;

/**
 * How strong a value's support is in the literature, or how well it carries
 * over from people to organizations (03 · 3.2); the mégis sets it.
 */
enum ValueGrade: string
{
    case Strong = 'strong';
    case Medium = 'medium';
    case Weak = 'weak';

    public function label(): string
    {
        return __('enums.value_grade.'.$this->value);
    }
}
