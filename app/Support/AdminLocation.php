<?php

namespace App\Support;

use App\Models\Organization;

/**
 * Where an admin page sits (AdminBreadcrumbs): the pages above it, the
 * organization it belongs to and its tab lit in the header, the URL its tabs
 * hang under, the menu item it lights and the menu group named in the header.
 */
final readonly class AdminLocation
{
    /**
     * @param  list<array{label: string, url: string}>  $trail  the pages above, the topmost first; the page itself is not in it
     * @param  string|null  $tab  the route of the organization's tab the page belongs to
     * @param  string|null  $home  the URL the page's tabs share; the page itself when null
     * @param  string|null  $section  the route of the menu item the page belongs to
     * @param  string|null  $area  the menu group of that item, the header's name outside an organization
     */
    public function __construct(
        public array $trail = [],
        public ?Organization $organization = null,
        public ?string $tab = null,
        public ?string $home = null,
        public ?string $section = null,
        public ?string $area = null,
    ) {}

    /**
     * The page right above, the one "up" leads to.
     *
     * @return array{label: string, url: string}|null
     */
    public function parent(): ?array
    {
        return $this->trail === [] ? null : $this->trail[array_key_last($this->trail)];
    }
}
