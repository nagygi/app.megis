<?php

namespace App\Policies;

use App\Models\Capability;
use App\Models\User;

/**
 * Every admin and superadmin may manage this on the admin side.
 */
class CapabilityPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, Capability $model): bool
    {
        return $actor->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, Capability $model): bool
    {
        return $actor->isAdmin();
    }

    /**
     * "Frissítés a mégis tárából" (07 · 7.6).
     */
    public function import(User $actor): bool
    {
        return $actor->isAdmin();
    }
}
