<?php

namespace App\Policies;

use App\Models\Diagnosis;
use App\Models\User;

/**
 * Every admin and superadmin may manage this on the admin side; the Futás
 * tab belongs to the organization's contact and the superadmin.
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

    /**
     * The Futás tab of a main diagnosis: the organization's contact and the
     * superadmin; a substitute is made the contact first (19 · 19.1).
     */
    public function run(User $actor, Diagnosis $model): bool
    {
        return $actor->isSuperadmin() || ($actor->isAdmin() && $model->organization?->contact_admin_id === $actor->id);
    }
}
