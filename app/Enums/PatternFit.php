<?php

namespace App\Enums;

/**
 * How exactly a pattern fits a linked capability ("illeszkedés").
 */
enum PatternFit: string
{
    case Exact = 'exact';
    case Partial = 'partial';

    public function label(): string
    {
        return __('enums.pattern_fit.'.$this->value);
    }
}
