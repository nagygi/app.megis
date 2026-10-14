<?php

use App\Enums\CheckStatus;
use App\Services\Checks\TurnstileConfigurationCheck;

it('reports an error when a key is missing', function () {
    config(['services.turnstile.site_key' => 'site-key', 'services.turnstile.secret_key' => null]);

    $result = app(TurnstileConfigurationCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Error);
});

it('rejects the Cloudflare test keys in production', function () {
    $this->app['env'] = 'production';
    config([
        'services.turnstile.site_key' => '1x00000000000000000000AA',
        'services.turnstile.secret_key' => '1x0000000000000000000000000000000AA',
    ]);

    $result = app(TurnstileConfigurationCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Error)
        ->and($result->message)->toBe('Élesben a Cloudflare tesztkulcsai nem használhatók.');
});

it('accepts the Cloudflare test keys outside production', function () {
    config([
        'services.turnstile.site_key' => '1x00000000000000000000AA',
        'services.turnstile.secret_key' => '1x0000000000000000000000000000000AA',
    ]);

    $result = app(TurnstileConfigurationCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Ok);
});
