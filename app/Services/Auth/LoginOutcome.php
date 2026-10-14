<?php

namespace App\Services\Auth;

/**
 * Result of a password login attempt.
 */
enum LoginOutcome
{
    case Success;

    /** Wrong address or password, or an account that may not log in: the same message. */
    case Invalid;

    /** Admin account locked after 10 failures (01 · 1.2). */
    case Locked;

    /** Customer password login paused for 15 minutes after 10 failures (14 · 14.2). */
    case PasswordPaused;
}
