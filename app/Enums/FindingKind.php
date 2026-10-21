<?php

namespace App\Enums;

/**
 * A finding matches a pattern, or, for a capability without patterns,
 * interprets its definition: always approved by a person, at most low.
 */
enum FindingKind: string
{
    case Pattern = 'pattern';
    case Interpretation = 'interpretation';

    public function label(): string
    {
        return __('enums.finding_kind.'.$this->value);
    }
}
