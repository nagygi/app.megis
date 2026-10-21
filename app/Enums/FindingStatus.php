<?php

namespace App\Enums;

/**
 * A finding is a proposal until an admin confirms or rejects it; a rejected
 * proposal is a false alarm of its pattern (NY-27).
 */
enum FindingStatus: string
{
    case Proposed = 'proposed';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __('enums.finding_status.'.$this->value);
    }
}
