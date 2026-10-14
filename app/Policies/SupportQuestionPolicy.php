<?php

namespace App\Policies;

use App\Models\SupportQuestion;
use App\Models\User;

/**
 * Every admin and superadmin may manage this on the admin side.
 */
class SupportQuestionPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, SupportQuestion $model): bool
    {
        return $actor->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, SupportQuestion $model): bool
    {
        return $actor->isAdmin();
    }
}
