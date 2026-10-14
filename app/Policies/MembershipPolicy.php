<?php

namespace App\Policies;

use App\Models\Membership;
use App\Models\User;

/**
 * Every admin and superadmin may manage this on the admin side.
 */
class MembershipPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, Membership $model): bool
    {
        return $actor->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, Membership $model): bool
    {
        return $actor->isAdmin();
    }
}
