<?php

namespace App\Enums;

use Carbon\CarbonInterval;

/**
 * One-time links and codes sent by e-mail.
 */
enum TokenPurpose: string
{
    case AdminInvitation = 'admin_invitation';
    case AdminPasswordReset = 'admin_password_reset';
    case ClientInvitation = 'client_invitation';
    case LoginCode = 'login_code';
    case PasswordConfirmationCode = 'password_confirmation_code';

    /**
     * Admin links live 24 hours (01), customer invitations 14 days (14 · 14.1),
     * login and confirmation codes 15 minutes (14 · 14.2).
     */
    public function lifetime(): CarbonInterval
    {
        return match ($this) {
            self::AdminInvitation, self::AdminPasswordReset => CarbonInterval::hours(24),
            self::ClientInvitation => CarbonInterval::days(14),
            self::LoginCode, self::PasswordConfirmationCode => CarbonInterval::minutes(15),
        };
    }

    public function hasCode(): bool
    {
        return in_array($this, [self::LoginCode, self::PasswordConfirmationCode], true);
    }
}
