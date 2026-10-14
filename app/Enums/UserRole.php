<?php

namespace App\Enums;

/**
 * Admin roles (01). Customers have no role; the same account may be both.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Superadmin = 'superadmin';

    public function label(): string
    {
        return __('enums.user_role.'.$this->value);
    }
}
