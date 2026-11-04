<?php

use App\Enums\TokenPurpose;
use App\Mail\ClientPasswordResetMail;
use App\Mail\PasswordChangedMail;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Services\Auth\TokenService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;

function passwordLink(User $user): string
{
    return app(TokenService::class)->issue($user, TokenPurpose::ClientPasswordReset)->plainToken;
}

function postNewPassword(string $token, string $password = 'uj-jelszo-5678', ?string $confirmation = null): TestResponse
{
    return test()->post(route('client.password.update', $token), [
        'password' => $password,
        'password_confirmation' => $confirmation ?? $password,
        'cf-turnstile-response' => 'turnstile-token',
    ]);
}

it('only shows the form when the link is opened, so mail scanners do not use it up', function () {
    $customer = customerOf();
    $token = passwordLink($customer);

    $this->get(route('client.password.reset', $token))
        ->assertOk()
        ->assertSee('Új jelszó beállítása')
        ->assertSee($customer->email)
        ->assertSee('Beállítom és belépek');

    expect($customer->tokens()->sole()->consumed_at)->toBeNull();
    $this->assertGuest('web');
});

it('sets the new password, logs in and ends every other session (14 · 14.3)', function () {
    fakeTurnstile();
    Mail::fake();
    $organization = Organization::factory()->create();
    $customer = customerOf($organization);
    $customer->forceFill([
        'password' => Hash::make('regi-jelszo-1234'),
        'failed_password_attempts' => 4,
        'password_paused_until' => now()->addMinutes(10),
    ])->save();
    $epoch = (int) $customer->auth_epoch;
    $token = passwordLink($customer);

    $response = postNewPassword($token);

    $response->assertRedirect(route('client.home', $organization));
    $this->assertAuthenticatedAs($customer, 'web');

    $customer->refresh();
    expect(Hash::check('uj-jelszo-5678', $customer->password))->toBeTrue()
        ->and($customer->auth_epoch)->toBeGreaterThan($epoch)
        ->and($customer->failed_password_attempts)->toBe(0)
        ->and($customer->password_paused_until)->toBeNull()
        ->and($customer->tokens()->sole()->consumed_at)->not->toBeNull();
    Mail::assertQueued(PasswordChangedMail::class, fn (PasswordChangedMail $mail) => $mail->hasTo($customer->email));
});

it('keeps the old password until the new one is saved', function () {
    fakeTurnstile();
    $customer = customerOf();
    $customer->forceFill(['password' => Hash::make('regi-jelszo-1234')])->save();

    passwordLink($customer);

    expect(Hash::check('regi-jelszo-1234', $customer->refresh()->password))->toBeTrue();
});

it('says the link is no longer valid after use or after 60 minutes', function (Closure $spoil) {
    fakeTurnstile();
    Mail::fake();
    $customer = customerOf();
    $token = passwordLink($customer);

    $spoil($token);

    $this->get(route('client.password.reset', $token))
        ->assertOk()
        ->assertSee('Ez a link már nem érvényes')
        ->assertSee(route('client.password.request'));
    postNewPassword($token, 'masik-jelszo-9012')->assertOk()->assertSee('Ez a link már nem érvényes');
    expect(Hash::check('masik-jelszo-9012', (string) $customer->refresh()->password))->toBeFalse();
})->with([
    'used' => function (string $token) {
        postNewPassword($token)->assertRedirect();
        test()->post(route('client.logout'));
    },
    'expired' => fn () => test()->travel(61)->minutes(),
]);

it('refuses an unknown link', function () {
    $this->get(route('client.password.reset', 'nincs-ilyen'))->assertOk()->assertSee('Ez a link már nem érvényes');
});

it('refuses the link of an admin account', function () {
    fakeTurnstile();
    $admin = User::factory()->admin()->create();
    Membership::factory()->for($admin)->create();
    $token = passwordLink($admin);

    $this->get(route('client.password.reset', $token))->assertSee('Ez a link már nem érvényes');
    postNewPassword($token)->assertSee('Ez a link már nem érvényes');

    $this->assertGuest('web');
});

it('refuses the link once the account is inactivated', function () {
    $customer = customerOf();
    $token = passwordLink($customer);
    $customer->forceFill(['deactivated_at' => now()])->save();

    $this->get(route('client.password.reset', $token))->assertSee('Ez a link már nem érvényes');
});

it('checks the password rule and the confirmation, and keeps the link', function () {
    fakeTurnstile();
    $customer = customerOf();
    $token = passwordLink($customer);

    postNewPassword($token, 'rovid')->assertSessionHasErrors('password');
    postNewPassword($token, 'uj-jelszo-5678', 'mas-jelszo-5678')->assertSessionHasErrors('password');

    $this->assertGuest('web');
    expect($customer->tokens()->sole()->consumed_at)->toBeNull();
});

it('renders the mail with the link, the 60 minutes and who sent it', function () {
    $customer = customerOf();

    $own = (new ClientPasswordResetMail($customer, 'abc'))->render();
    $byAdmin = (new ClientPasswordResetMail($customer, 'abc', byAdmin: true))->render();

    expect($own)->toContain(route('client.password.reset', 'abc'))
        ->toContain('Új jelszót kértél')
        ->toContain('60 percig él')
        ->and($byAdmin)->toContain('A mégis adminisztrátora jelszó-beállító linket küldött neked');
});
