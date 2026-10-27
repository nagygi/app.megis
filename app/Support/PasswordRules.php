<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * One password rule for every account, admins included (01 · 1.2, 14 · 14.3):
 * at least 8 characters, and no known leaked password.
 */
class PasswordRules
{
    public const MIN_LENGTH = 8;

    public static function rule(): Password
    {
        return Password::min(self::MIN_LENGTH)->uncompromised();
    }

    /**
     * The rule text shown under every password field (14 · 14.3).
     */
    public static function hint(): string
    {
        return __('auth_ui.password_hint', ['min' => self::MIN_LENGTH]);
    }
}
