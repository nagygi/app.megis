<?php

namespace App\Enums;

/**
 * The primary level of a capability (07 · 7.2). Ecosystem: the organization's
 * relations with clients, partners, suppliers, the community and nature.
 */
enum CapabilityLevel: string
{
    case Individual = 'individual';
    case Team = 'team';
    case Organization = 'organization';
    case Ecosystem = 'ecosystem';

    public function label(): string
    {
        return __('enums.capability_level.'.$this->value);
    }
}
