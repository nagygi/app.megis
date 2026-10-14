<?php

namespace App\Policies;

use App\Models\Material;
use App\Models\User;

/**
 * Every admin and superadmin may manage this on the admin side.
 */
class MaterialPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, Material $model): bool
    {
        return $actor->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, Material $model): bool
    {
        return $actor->isAdmin();
    }
}
