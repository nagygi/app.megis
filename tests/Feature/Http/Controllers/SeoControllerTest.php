<?php

it('disallows every path outside production', function () {
    $response = $this->get('/robots.txt');

    $response->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    expect($response->getContent())->toBe("User-agent: *\nDisallow: /\n");
});

it('allows public pages and links the sitemap in production', function () {
    $this->app['env'] = 'production';

    $response = $this->get('/robots.txt');

    $response->assertOk()
        ->assertSee('Disallow: /admin', escape: false)
        ->assertSee('Sitemap: '.route('sitemap'), escape: false)
        ->assertDontSee("Disallow: /\n", escape: false);
});

it('lists the configured public pages in the sitemap', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('<loc>'.route('public.home').'</loc>', escape: false);
});

it('skips sitemap entries whose route does not exist', function () {
    config(['megis.sitemap_routes' => ['public.home', 'missing.route']]);

    $response = $this->get('/sitemap.xml');

    $response->assertOk()->assertDontSee('missing.route');
});
