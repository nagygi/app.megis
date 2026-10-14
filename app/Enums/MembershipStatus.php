<?php

namespace App\Enums;

/**
 * Stored status of a user in an organization (02 · 2.5). "Meghívó lejárt" is
 * not stored: it is an invited membership whose invitation link expired.
 */
enum MembershipStatus: string
{
    case Added = 'added';
    case Invited = 'invited';
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return __('enums.membership_status.'.$this->value);
    }

    /**
     * Everyone except inactive members counts towards the headcount limit, partners included.
     */
    public function countsTowardsLimit(): bool
    {
        return $this !== self::Inactive;
    }
}
