<?php

namespace App\Policies;

use App\Models\Area;
use App\Models\User;

/**
 * Every admin and superadmin edits the texts of the areas (18 · 18.1);
 * the list itself is fixed.
 */
class AreaPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, Area $model): bool
    {
        return $actor->isAdmin();
    }
}
