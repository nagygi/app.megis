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
    case ClientPasswordReset = 'client_password_reset';
    case MirrorConfirmation = 'mirror_confirmation';
    // The personal link of a client's user to the Ügyfélmérés (35 · 35.5).
    case ExternalClientInvitation = 'external_client_invitation';

    /**
     * Admin links live 24 hours (01), customer invitations 14 days (14 · 14.1),
     * login and confirmation codes 15 minutes (14 · 14.2). The Értéktükör
     * confirmation link lives as long as the anonymous mirror: 24 hours (06 · 6.1).
     * A customer's password link lives 60 minutes (14 · 14.3). A client user's
     * link lives 14 days, as an invitation (35 · 35.5).
     */
    public function lifetime(): CarbonInterval
    {
        return match ($this) {
            self::AdminInvitation, self::AdminPasswordReset, self::MirrorConfirmation => CarbonInterval::hours(24),
            self::ClientInvitation, self::ExternalClientInvitation => CarbonInterval::days(14),
            self::LoginCode, self::PasswordConfirmationCode => CarbonInterval::minutes(15),
            self::ClientPasswordReset => CarbonInterval::minutes(60),
        };
    }

    public function hasCode(): bool
    {
        return in_array($this, [self::LoginCode, self::PasswordConfirmationCode], true);
    }
}
