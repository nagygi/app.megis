<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Value;

/**
 * Every admin and superadmin may manage this on the admin side; the import
 * is the superadmin's.
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

    /**
     * "Érték Import" (03 · 3.6): a load rewrites many values at once, so it
     * is the superadmin's; an admin sees the page, inactive.
     */
    public function import(User $actor): bool
    {
        return $actor->isSuperadmin();
    }
}
