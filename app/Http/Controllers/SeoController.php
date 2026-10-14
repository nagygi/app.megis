<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

/**
 * robots.txt and sitemap.xml (NFR · 6 and 9). Only production may be indexed,
 * and only public pages are listed.
 */
class SeoController extends Controller
{
    /**
     * Paths that are never meant for search engines, even in production.
     *
     * @var list<string>
     */
    private const DISALLOWED_PATHS = ['/admin', '/belepes', '/meghivo', '/livewire'];

    public function robots(): Response
    {
        $lines = ['User-agent: *'];

        if (app()->isProduction()) {
            foreach (self::DISALLOWED_PATHS as $path) {
                $lines[] = 'Disallow: '.$path;
            }

            $lines[] = 'Sitemap: '.route('sitemap');
        } else {
            $lines[] = 'Disallow: /';
        }

        return response(implode("\n", $lines)."\n")
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function sitemap(): Response
    {
        $urls = collect(config('megis.sitemap_routes'))
            ->filter(fn (string $routeName): bool => Route::has($routeName))
            ->map(fn (string $routeName): string => route($routeName))
            ->values();

        return response()
            ->view('seo.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
