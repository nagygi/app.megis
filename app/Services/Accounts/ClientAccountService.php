<?php

namespace App\Services\Accounts;

use App\Enums\AuditEventType;
use App\Models\User;
use App\Services\Audit\AuditLogger;

/**
 * A customer account inactivated or activated by an admin (33 · 33.2). The
 * memberships stay as they are; an inactive account cannot sign in.
 */
class ClientAccountService
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Signed out at once.
     */
    public function deactivate(User $user, User $actor): void
    {
        $user->forceFill(['deactivated_at' => now()])->save();
        $user->invalidateSessions();

        $this->audit->record(AuditEventType::AccountDeactivated, $user, $actor);
    }

    public function activate(User $user, User $actor): void
    {
        $user->forceFill(['deactivated_at' => null])->save();

        $this->audit->record(AuditEventType::AccountActivated, $user, $actor);
    }
}
