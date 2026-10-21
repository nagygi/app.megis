<?php

namespace App\Enums;

/**
 * Where a pattern comes from ("eredet").
 */
enum PatternOrigin: string
{
    case OwnCase = 'own_case';
    case External = 'external';
    case Both = 'both';

    public function label(): string
    {
        return __('enums.pattern_origin.'.$this->value);
    }
}
