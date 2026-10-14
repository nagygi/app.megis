<?php

namespace App\Policies;

use App\Models\InboxItem;
use App\Models\User;

/**
 * Every admin and superadmin may manage this on the admin side.
 */
class InboxItemPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, InboxItem $model): bool
    {
        return $actor->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, InboxItem $model): bool
    {
        return $actor->isAdmin();
    }
}
