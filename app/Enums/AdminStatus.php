<?php

namespace App\Enums;

/**
 * Display status of an admin account (01 · 1.3), derived from the user record.
 */
enum AdminStatus: string
{
    case Invited = 'invited';
    case InvitationExpired = 'invitation_expired';
    case Active = 'active';
    case Locked = 'locked';
    case Inactive = 'inactive';

    public function label(): string
    {
        return __('enums.admin_status.'.$this->value);
    }
}
