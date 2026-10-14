<?php

it('marks pages noindex outside production', function () {
    $response = $this->get('/');

    $response->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('lets production pages be indexed', function () {
    $this->app['env'] = 'production';

    $response = $this->get('/');

    $response->assertOk()->assertHeaderMissing('X-Robots-Tag');
});
