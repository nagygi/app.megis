<?php

namespace App\Services\Organizations;

use App\Models\Organization;
use App\Support\ReservedSlugs;
use Illuminate\Support\Str;

/**
 * Organization slugs: lower case, no accents, hyphens (02 · 2.3), never a system route.
 */
class SlugGenerator
{
    public function __construct(private ReservedSlugs $reservedSlugs) {}

    public function fromName(string $name, ?int $ignoreOrganizationId = null): string
    {
        $base = Str::slug($name);
        $base = $base === '' ? 'szervezet' : $base;

        $slug = $base;
        $suffix = 2;

        while (! $this->isAvailable($slug, $ignoreOrganizationId)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
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
