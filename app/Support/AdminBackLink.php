<?php

namespace App\Support;

use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Where the Vissza of an admin page leads: back to the admin page it was
 * opened from, a list with its filters and page included. The tabs of a page
 * share it, coming back from a page opened here keeps it, and a create form
 * hands its own on to the page it redirects to. Opened from outside the
 * admin, the page leads to its list (the fallback).
 */
class AdminBackLink
{
    /**
     * The home of a page => the URL it was opened from, the latest first.
     */
    private const SESSION_KEY = 'admin_back';

    private const LIMIT = 50;

    /**
     * The forms that redirect to what they create.
     */
    private const CREATE_FORMS = ['admin.*.create', 'admin.diagnoses.upload'];

    public function __construct(
        private readonly Request $request,
        private readonly Router $router,
        private readonly Session $session,
    ) {}

    /**
     * @param  string|null  $home  the URL the page's tabs hang under; the page itself by default
     */
    public function url(string $fallback, ?string $home = null): string
    {
        // A Livewire update comes to livewire/update; its snapshot keeps the page.
        $home = $this->path($home ?? Livewire::originalPath());
        $origins = $this->session->get(self::SESSION_KEY, []);
        $referer = $this->referer();

        if ($referer === null) {
            return $this->forget($origins, $home, $fallback);
        }

        [$url, $route] = $referer;
        $refererPath = $this->path($url);
        $refererOrigin = $this->originOf($refererPath, $origins);

        // A tab of the page, the page itself (a reload, a Livewire update), or the way back from a page opened here.
        if ($this->within($refererPath, $home) || ($refererOrigin !== null && $this->within($this->path($refererOrigin), $home))) {
            return $origins[$home] ?? $fallback;
        }

        $origin = $route->named(...self::CREATE_FORMS) ? $refererOrigin : $url;

        if ($origin === null) {
            return $this->forget($origins, $home, $fallback);
        }

        $this->session->put(self::SESSION_KEY, array_slice([$home => $origin] + Arr::except($origins, $home), 0, self::LIMIT, true));

        return $origin;
    }

    /**
     * The admin page the request came from, with its route; null from anywhere else.
     *
     * @return array{string, Route}|null
     */
    private function referer(): ?array
    {
        $url = $this->request->headers->get('referer');

        if ($url === null || parse_url($url, PHP_URL_HOST) !== $this->request->getHost()) {
            return null;
        }

        try {
            $route = $this->router->getRoutes()->match(Request::create($url));
        } catch (HttpException) {
            return null;
        }

        return in_array('admin', $route->gatherMiddleware(), true) ? [$url, $route] : null;
    }

    /**
     * The origin of the page at the path: that of the closest home it is within.
     *
     * @param  array<string, string>  $origins
     */
    private function originOf(string $path, array $origins): ?string
    {
        $home = collect(array_keys($origins))
            ->filter(fn (string $home) => $this->within($path, $home))
            ->sortByDesc(fn (string $home) => strlen($home))
            ->first();

        return $home === null ? null : $origins[$home];
    }

    /**
     * @param  array<string, string>  $origins
     */
    private function forget(array $origins, string $home, string $fallback): string
    {
        if (array_key_exists($home, $origins)) {
            $this->session->put(self::SESSION_KEY, Arr::except($origins, $home));
        }

        return $fallback;
    }

    private function within(string $path, string $home): bool
    {
        return $path === $home || str_starts_with($path, $home.'/');
    }

    private function path(string $url): string
    {
        return trim((string) parse_url($url, PHP_URL_PATH), '/');
    }
}
