<?php

use App\Enums\TokenPurpose;
use App\Models\Organization;
use App\Models\User;
use App\Services\Auth\TokenService;

it('stores only keyed hashes of the link and the code', function () {
    $user = User::factory()->create();

    $issued = app(TokenService::class)->issue($user, TokenPurpose::LoginCode);

    expect($issued->token->token_hash)->not->toBe($issued->plainToken)
        ->and($issued->token->token_hash)->not->toBe(hash('sha256', $issued->plainToken))
        ->and($issued->plainCode)->toMatch('/^\d{6}$/');
});

it('voids the previous token of the same purpose', function () {
    $user = User::factory()->create();
    $tokens = app(TokenService::class);
    $first = $tokens->issue($user, TokenPurpose::LoginCode);

    $tokens->issue($user, TokenPurpose::LoginCode);

    expect($first->token->fresh()->isPending())->toBeFalse();
});

it('keeps invitations of other organizations valid', function () {
    $user = User::factory()->invited()->create();
    $tokens = app(TokenService::class);
    $first = $tokens->issue($user, TokenPurpose::ClientInvitation, Organization::factory()->create());

    $tokens->issue($user, TokenPurpose::ClientInvitation, Organization::factory()->create());

    expect($first->token->fresh()->isPending())->toBeTrue();
});

it('voids a code on the fifth wrong attempt', function () {
    $user = User::factory()->create();
    $tokens = app(TokenService::class);
    $issued = $tokens->issue($user, TokenPurpose::LoginCode);
    $wrongCode = $issued->plainCode === '123456' ? '654321' : '123456';

    foreach (range(1, 4) as $attempt) {
        expect($tokens->checkCode($issued->token->fresh(), $wrongCode))->toBeFalse();
    }
    expect($issued->token->fresh()->isPending())->toBeTrue();

    $tokens->checkCode($issued->token->fresh(), $wrongCode);

    expect($issued->token->fresh()->isPending())->toBeFalse()
        ->and($tokens->checkCode($issued->token->fresh(), $issued->plainCode))->toBeFalse();
});

it('accepts the right code while pending', function () {
    $user = User::factory()->create();
    $tokens = app(TokenService::class);
    $issued = $tokens->issue($user, TokenPurpose::PasswordConfirmationCode);

    expect($tokens->checkCode($issued->token, $issued->plainCode))->toBeTrue();
});
