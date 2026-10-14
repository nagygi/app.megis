<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\Rules\Password;

/**
 * The password rule depends on the account, not on the screen: an admin keeps
 * the admin rule even when changing the password on the customer side.
 */
class PasswordRules
{
    public const ADMIN_MIN_LENGTH = 12;

    public const CUSTOMER_MIN_LENGTH = 8;

    public static function for(?User $user): Password
    {
        return Password::min(self::minLengthFor($user))->uncompromised();
    }

    public static function minLengthFor(?User $user): int
    {
        return $user?->isAdmin() ? self::ADMIN_MIN_LENGTH : self::CUSTOMER_MIN_LENGTH;
    }

    public static function admin(): Password
    {
        return Password::min(self::ADMIN_MIN_LENGTH)->uncompromised();
    }

    /**
     * The rule text shown under every password field (14 · 14.3).
     */
    public static function hintFor(?User $user): string
    {
        return __('auth_ui.password_hint', ['min' => self::minLengthFor($user)]);
    }
}
