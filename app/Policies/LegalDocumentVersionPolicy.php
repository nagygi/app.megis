<?php

namespace App\Policies;

use App\Models\LegalDocumentVersion;
use App\Models\User;

/**
 * Every admin and superadmin may manage this on the admin side.
 */
class LegalDocumentVersionPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, LegalDocumentVersion $model): bool
    {
        return $actor->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, LegalDocumentVersion $model): bool
    {
        return $actor->isAdmin();
    }
}
