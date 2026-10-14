<?php

use App\Enums\CheckStatus;
use App\Services\Checks\MailConfigurationCheck;

it('reports an error when production only logs mail', function () {
    $this->app['env'] = 'production';
    config(['mail.default' => 'log']);

    $result = app(MailConfigurationCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Error)
        ->and($result->message)->toBe('Élesben nem lehet „log” a levélküldő.');
});

it('accepts a local mail catcher outside production', function () {
    config(['mail.default' => 'log']);

    $result = app(MailConfigurationCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Ok);
});

it('reports an error when the SMTP server cannot be reached', function () {
    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.host' => '127.0.0.1',
        'mail.mailers.smtp.port' => 1,
        'mail.mailers.smtp.timeout' => 1,
    ]);

    $result = app(MailConfigurationCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Error);
});
