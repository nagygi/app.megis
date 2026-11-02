<?php

namespace App\Support;

use App\Models\Organization;

/**
 * Where an admin page sits (AdminBreadcrumbs): the pages above it, the
 * organization it belongs to, the URL its tabs hang under and the menu item
 * it lights.
 */
final readonly class AdminLocation
{
    /**
     * @param  list<array{label: string, url: string}>  $trail  the pages above, the topmost first; the page itself is not in it
     * @param  string|null  $home  the URL the page's tabs share; the page itself when null
     * @param  string|null  $section  the route of the menu item the page belongs to
     */
    public function __construct(
        public array $trail = [],
        public ?Organization $organization = null,
        public ?string $home = null,
        public ?string $section = null,
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
