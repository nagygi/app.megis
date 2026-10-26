<?php

namespace App\Policies;

use App\Models\MirrorSession;
use App\Models\User;

/**
 * Every admin and superadmin may look at the mirrors on the admin side (Értékmérések).
 */
class MirrorSessionPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, MirrorSession $model): bool
    {
        return $actor->isAdmin();
    }
}
