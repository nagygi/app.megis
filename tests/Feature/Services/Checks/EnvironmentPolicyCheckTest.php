<?php

use App\Enums\CheckStatus;
use App\Services\Checks\EnvironmentPolicyCheck;

it('reports an error when debug mode is on in production', function () {
    $this->app['env'] = 'production';
    config(['app.debug' => true, 'app.url' => 'https://app.megis.hu']);

    $result = app(EnvironmentPolicyCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Error)
        ->and($result->message)->toBe('Élesben az APP_DEBUG nem lehet bekapcsolva.');
});

it('reports an error when the production URL is not https', function () {
    $this->app['env'] = 'production';
    config(['app.debug' => false, 'app.url' => 'http://app.megis.hu']);

    $result = app(EnvironmentPolicyCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Error)
        ->and($result->message)->toBe('Élesben az APP_URL csak https lehet.');
});

it('warns when staging is neither password nor IP protected', function () {
    $this->app['env'] = 'staging';
    config([
        'megis.staging.basic_auth_user' => null,
        'megis.staging.basic_auth_password' => null,
        'megis.staging.allowed_ips' => [],
    ]);

    $result = app(EnvironmentPolicyCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Warning)
        ->and($result->message)->toContain('A staging nincs védve');
});

it('accepts a protected staging that sends mail to everyone', function () {
    $this->app['env'] = 'staging';
    config([
        'megis.staging.allowed_ips' => ['10.0.0.1'],
        'megis.mail_allowlist' => [],
    ]);

    $result = app(EnvironmentPolicyCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Ok);
});

it('passes a correctly configured production environment', function () {
    $this->app['env'] = 'production';
    config(['app.debug' => false, 'app.url' => 'https://app.megis.hu']);

    $result = app(EnvironmentPolicyCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Ok);
});
