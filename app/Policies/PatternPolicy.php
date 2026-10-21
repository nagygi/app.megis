<?php

namespace App\Policies;

use App\Models\Pattern;
use App\Models\User;

/**
 * Every admin may read the Mintatár (NM-19). It is loaded from the mégis
 * Sheet, so there is nothing to edit here.
 */
class PatternPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, Pattern $model): bool
    {
        return $actor->isAdmin();
    }
}
