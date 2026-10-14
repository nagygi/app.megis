<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ValueCategory;

/**
 * Every admin and superadmin may manage this on the admin side.
 */
class ValueCategoryPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, ValueCategory $model): bool
    {
        return $actor->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, ValueCategory $model): bool
    {
        return $actor->isAdmin();
    }
}
