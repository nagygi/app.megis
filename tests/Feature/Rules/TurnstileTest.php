<?php

use App\Rules\Turnstile;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

const TURNSTILE_VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

beforeEach(function () {
    config(['services.turnstile.secret_key' => 'turnstile-secret']);
});

function validateTurnstile(?string $token): Illuminate\Validation\Validator
{
    return Validator::make(
        [Turnstile::FIELD => $token],
        [Turnstile::FIELD => [new Turnstile('203.0.113.9')]],
    );
}

it('passes when Cloudflare confirms the token', function () {
    Http::fake([TURNSTILE_VERIFY_URL => Http::response(['success' => true])]);

    $validator = validateTurnstile('valid-token');

    expect($validator->passes())->toBeTrue();
    Http::assertSent(fn (Request $request): bool => $request['secret'] === 'turnstile-secret'
        && $request['response'] === 'valid-token'
        && $request['remoteip'] === '203.0.113.9');
});

it('fails with a Hungarian message when Cloudflare rejects the token', function () {
    Http::fake([TURNSTILE_VERIFY_URL => Http::response(['success' => false, 'error-codes' => ['invalid-input-response']])]);

    $validator = validateTurnstile('forged-token');

    expect($validator->errors()->first(Turnstile::FIELD))
        ->toBe('Nem sikerült ellenőrizni, hogy ember vagy. Próbáld újra.');
});

it('fails without calling Cloudflare when the token is missing', function () {
    Http::fake();

    $validator = validateTurnstile('');

    expect($validator->fails())->toBeTrue();
    Http::assertNothingSent();
});

it('fails when Cloudflare cannot be reached', function () {
    Http::fake([TURNSTILE_VERIFY_URL => fn () => throw new ConnectionException('timeout')]);

    $validator = validateTurnstile('valid-token');

    expect($validator->fails())->toBeTrue();
});
