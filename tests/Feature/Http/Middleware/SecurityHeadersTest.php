<?php

it('sends the browser hardening headers on every page', function () {
    $this->get('/')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), payment=(), microphone=(self)')
        ->assertHeaderMissing('Strict-Transport-Security');

    $this->get('/health')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
});

it('asks for https only in production over https', function () {
    $this->app['env'] = 'production';

    $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    $this->get('http://localhost/')->assertHeaderMissing('Strict-Transport-Security');
});
