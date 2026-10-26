<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

/**
 * robots.txt and sitemap.xml (NFR · 6 and 9). Only production may be crawled,
 * and there only the home page: everything else stays out of search engines.
 */
class SeoController extends Controller
{
    /**
     * What a crawler may fetch in production: the home page and the assets it
     * needs to render.
     *
     * @var list<string>
     */
    private const ALLOWED_PATHS = ['/$', '/build/'];

    public function robots(): Response
    {
        $lines = ['User-agent: *'];

        if (app()->isProduction()) {
            foreach (self::ALLOWED_PATHS as $path) {
                $lines[] = 'Allow: '.$path;
            }

            $lines[] = 'Disallow: /';
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
