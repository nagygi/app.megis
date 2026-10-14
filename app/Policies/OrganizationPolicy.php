<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

/**
 * Every admin and superadmin may manage this on the admin side.
 */
class OrganizationPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, Organization $model): bool
    {
        return $actor->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, Organization $model): bool
    {
        return $actor->isAdmin();
    }
}
