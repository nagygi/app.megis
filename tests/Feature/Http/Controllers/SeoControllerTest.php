<?php

it('disallows every path outside production', function () {
    $response = $this->get('/robots.txt');

    $response->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    expect($response->getContent())->toBe("User-agent: *\nDisallow: /\n");
});

it('allows only the home page and its assets in production, and links the sitemap', function () {
    $this->app['env'] = 'production';

    $response = $this->get('/robots.txt');

    $response->assertOk();
    expect($response->getContent())->toBe(
        "User-agent: *\nAllow: /$\nAllow: /build/\nDisallow: /\nSitemap: ".route('sitemap')."\n"
    );
});

it('lists only the home page in the sitemap', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('<loc>'.route('public.home').'</loc>', escape: false)
        ->assertDontSee('<loc>'.route('mirror.record').'</loc>', escape: false);
});

it('skips sitemap entries whose route does not exist', function () {
    config(['megis.sitemap_routes' => ['public.home', 'missing.route']]);

    $response = $this->get('/sitemap.xml');

    $response->assertOk()->assertDontSee('missing.route');
});
