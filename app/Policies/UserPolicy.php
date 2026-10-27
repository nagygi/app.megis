<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Who may manage which account (01 · Szerepkörök, 14 · 14.6, 33 · 2. kérdés).
 * The actor is always the logged-in admin.
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->isAdmin();
    }

    /**
     * Any admin corrects a customer's name on the datasheet; an admin
     * account's name only who may edit that account (33 · 33.2).
     */
    public function rename(User $actor, User $target): bool
    {
        return $target->isAdmin() ? $this->updateAdmin($actor, $target) : $actor->isAdmin();
    }

    /**
     * Only a superadmin may invite or appoint a superadmin.
     */
    public function inviteAdmin(User $actor, UserRole $role): bool
    {
        return $role === UserRole::Superadmin ? $actor->isSuperadmin() : $actor->isAdmin();
    }

    /**
     * An admin sees superadmins read-only.
     */
    public function updateAdmin(User $actor, User $target): bool
    {
        return $actor->isAdmin() && (! $target->isSuperadmin() || $actor->isSuperadmin());
    }

    public function resendInvitation(User $actor, User $target): bool
    {
        return $this->updateAdmin($actor, $target) && ! $target->isActivated() && $target->isActive();
    }

    public function resetPassword(User $actor, User $target): bool
    {
        return $this->updateAdmin($actor, $target)
            && ! $actor->is($target)
            && $target->isActive()
            && $target->isActivated();
    }

    /**
     * Nobody deactivates themselves, and the last active superadmin stays.
     */
    public function deactivate(User $actor, User $target): bool
    {
        return $this->updateAdmin($actor, $target)
            && ! $actor->is($target)
            && $target->isActive()
            && ! $this->isLastActiveSuperadmin($target);
    }

    public function activate(User $actor, User $target): bool
    {
        return $this->updateAdmin($actor, $target) && ! $target->isActive();
    }

    /**
     * Nobody demotes themselves; the last active superadmin cannot be demoted.
     */
    public function assignRole(User $actor, User $target, UserRole $role): bool
    {
        if (! $this->updateAdmin($actor, $target) || ! $this->inviteAdmin($actor, $role)) {
            return false;
        }

        if ($target->role === $role) {
            return true;
        }

        return ! $actor->is($target) && ! $this->isLastActiveSuperadmin($target);
    }

    /**
     * Own address and customers' addresses: superadmin only. Other admins'
     * addresses: anyone who may edit them.
     */
    public function changeEmail(User $actor, User $target): bool
    {
        if ($actor->is($target) || ! $target->isAdmin()) {
            return $actor->isSuperadmin();
        }

        return $this->updateAdmin($actor, $target);
    }

    /**
     * A customer account on the user list and datasheet (33 · 33.2). Admin
     * accounts are managed under Admin felhasználók; an archived one stays so.
     */
    public function deactivateClient(User $actor, User $target): bool
    {
        return $actor->isAdmin()
            && ! $target->isAdmin()
            && $target->isActive()
            && ! $target->isDeletionRequested();
    }

    public function activateClient(User $actor, User $target): bool
    {
        return $actor->isAdmin()
            && ! $target->isAdmin()
            && ! $target->isActive()
            && ! $target->isDeletionRequested();
    }

    /**
     * Archiving is the account deletion of 31 · 31.7, started by a superadmin.
     */
    public function archive(User $actor, User $target): bool
    {
        return $actor->isSuperadmin()
            && ! $target->isAdmin()
            && ! $target->isDeletionRequested();
    }

    /**
     * Only active customers, never admin accounts (14 · 14.6).
     */
    public function impersonate(User $actor, User $target): bool
    {
        return $actor->isAdmin()
            && ! $target->isAdmin()
            && $target->isActive()
            && $target->isActivated();
    }

    private function isLastActiveSuperadmin(User $target): bool
    {
        if (! $target->isSuperadmin() || ! $target->isActive()) {
            return false;
        }

        return User::query()
            ->where('role', UserRole::Superadmin)
            ->whereNull('deactivated_at')
            ->count() <= 1;
    }
}
