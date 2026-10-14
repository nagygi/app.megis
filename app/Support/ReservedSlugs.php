<?php

namespace App\Support;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;

/**
 * First URL segments an organization slug may not take, because the
 * organization pages live at /{slug}/… next to the system routes (02 · 2.3, 15.1).
 */
class ReservedSlugs
{
    public function __construct(private Router $router) {}

    /**
     * @return list<string>
     */
    public function all(): array
    {
        $routeSegments = collect($this->router->getRoutes()->getRoutes())
            ->map(fn (Route $route): string => explode('/', trim($route->uri(), '/'))[0])
            ->reject(fn (string $segment): bool => $segment === '' || str_starts_with($segment, '{'));

        return $routeSegments
            ->merge(config('megis.reserved_slugs'))
            ->map(fn (string $segment): string => mb_strtolower($segment))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function contains(string $slug): bool
    {
        return in_array(mb_strtolower($slug), $this->all(), true);
    }
}
