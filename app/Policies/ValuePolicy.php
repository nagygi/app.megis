<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Value;

/**
 * Every admin and superadmin may manage this on the admin side.
 */
class ValuePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, Value $model): bool
    {
        return $actor->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, Value $model): bool
    {
        return $actor->isAdmin();
    }
}
