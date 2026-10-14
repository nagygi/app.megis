<?php

namespace App\Services\Organizations;

use App\Models\Organization;
use App\Support\ReservedSlugs;
use App\Support\UniqueSlug;

/**
 * Organization slugs: lower case, no accents, hyphens (02 · 2.3), never a system route.
 */
class SlugGenerator
{
    public function __construct(private ReservedSlugs $reservedSlugs) {}

    public function fromName(string $name, ?int $ignoreOrganizationId = null): string
    {
        return UniqueSlug::make($name, 'szervezet', fn (string $slug): bool => $this->isAvailable($slug, $ignoreOrganizationId));
    }

    public function isAvailable(string $slug, ?int $ignoreOrganizationId = null): bool
    {
        if ($this->reservedSlugs->contains($slug)) {
            return false;
        }

        return ! Organization::query()
            ->where('slug', $slug)
            ->when($ignoreOrganizationId, fn ($query) => $query->whereKeyNot($ignoreOrganizationId))
            ->exists();
    }
}
