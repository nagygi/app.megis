<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

/**
 * Every admin and superadmin may manage this on the admin side.
 */
class TeamPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, Team $model): bool
    {
        return $actor->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, Team $model): bool
    {
        return $actor->isAdmin();
    }
}
