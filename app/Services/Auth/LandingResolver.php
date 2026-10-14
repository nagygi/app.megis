<?php

namespace App\Services\Auth;

use App\Models\MirrorSession;
use App\Models\Organization;
use App\Models\User;

/**
 * Where a customer arrives after logging in (14 · 14.4). The system picks the
 * page, not the user. Someone who has only made an Értéktükör sees their
 * values in the Értéktár (30); otherwise, until the diagnosis pages exist,
 * the organization home opens.
 */
class LandingResolver
{
    public function urlFor(User $user): string
    {
        $organization = $this->organizationFor($user);

        if ($organization === null) {
            return route('client.paused');
        }

        if ($organization->isIndividual() && MirrorSession::query()->where('user_id', $user->id)->whereNotNull('claimed_at')->exists()) {
            return route('client.values', $organization);
        }

        return route('client.home', $organization);
    }

    /**
     * The last used organization, or the first accessible one (15 · 15.4).
     */
    public function organizationFor(User $user): ?Organization
    {
        $organizations = $user->accessibleOrganizations()->orderBy('organizations.name')->get();

        return $organizations->firstWhere('id', $user->last_organization_id) ?? $organizations->first();
    }
}
