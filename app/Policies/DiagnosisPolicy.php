<?php

namespace App\Policies;

use App\Models\Diagnosis;
use App\Models\User;

/**
 * Every admin and superadmin may manage this on the admin side.
 */
class DiagnosisPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, Diagnosis $model): bool
    {
        return $actor->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, Diagnosis $model): bool
    {
        return $actor->isAdmin();
    }
}
