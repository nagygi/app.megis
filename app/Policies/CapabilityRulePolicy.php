<?php

namespace App\Policies;

use App\Models\User;

/**
 * Every admin may read the Szabálykönyv, which comes with the mégis tár.
 */
class CapabilityRulePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }
}
