<?php

namespace App\Policies;

use App\Models\DiagnosisType;
use App\Models\User;

/**
 * Every admin and superadmin may manage this on the admin side.
 */
class DiagnosisTypePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, DiagnosisType $model): bool
    {
        return $actor->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, DiagnosisType $model): bool
    {
        return $actor->isAdmin();
    }
}
