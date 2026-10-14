<?php

namespace App\Support;

use App\Models\Organization;

/**
 * The organization the customer is working in for this request: the one in the
 * URL, otherwise the last used one (15 · 15.4). Features that refer to the
 * "active organization" read it from here.
 */
class CurrentOrganization
{
    private ?Organization $organization = null;

    public function set(Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function get(): ?Organization
    {
        return $this->organization;
    }
}
