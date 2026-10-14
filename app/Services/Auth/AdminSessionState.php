<?php

namespace App\Services\Auth;

use App\Models\User;

/**
 * Whether an admin session may continue: account still active, session not
 * older than the last password change or reset, and not idle for too long (01 · 1.2).
 */
class AdminSessionState
{
    public function __construct(private GuardSession $guardSession) {}

    public function isAlive(User $admin): bool
    {
        if (! $admin->isActive() || ! $this->guardSession->epochMatches(GuardSession::ADMIN, $admin)) {
            return false;
        }

        $lastActivity = $this->guardSession->adminLastActivity();

        return $lastActivity !== null
            && now()->getTimestamp() - $lastActivity <= config('megis.admin_idle_timeout_minutes') * 60;
    }
}
