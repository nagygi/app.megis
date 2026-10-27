<?php

use App\Enums\TokenPurpose;
use App\Mail\PasswordChangedMail;
use App\Models\User;
use App\Services\Auth\TokenService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;

function adminLink(User $user, TokenPurpose $purpose = TokenPurpose::AdminInvitation): string
{
    return app(TokenService::class)->issue($user, $purpose)->plainToken;
}

function postPasswordSetup(string $token, string $password): TestResponse
{
    return test()->post(route('admin.password.setup.store', $token), [
        'password' => $password,
        'password_confirmation' => $password,
        'cf-turnstile-response' => 'turnstile-token',
    ]);
}

it('shows the password form without using up the link', function () {
    $admin = User::factory()->admin()->invited()->create();
    $token = adminLink($admin);

    $response = $this->get(route('admin.password.setup', $token));

    $response->assertOk()->assertSee($admin->email);
    expect($admin->tokens()->sole()->consumed_at)->toBeNull();
});

it('sets the password, activates the account and logs the admin in', function () {
    fakeTurnstile();
    $admin = User::factory()->admin()->invited()->create();
    $token = adminLink($admin);

    $response = postPasswordSetup($token, 'a-long-enough-password');

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($admin, 'admin');
    $admin->refresh();
    expect(Hash::check('a-long-enough-password', $admin->password))->toBeTrue()
        ->and($admin->activated_at)->not->toBeNull()
        ->and($admin->tokens()->sole()->consumed_at)->not->toBeNull();
});

it('requires at least 8 characters for an admin password, as for a customer', function () {
    fakeTurnstile();
    $admin = User::factory()->admin()->invited()->create();

    $response = postPasswordSetup(adminLink($admin), 'short-1');

    $response->assertSessionHasErrors(['password' => 'A(z) jelszó legalább 8 karakter kell legyen.']);
});

it('accepts an 8 character admin password', function () {
    fakeTurnstile();
    $admin = User::factory()->admin()->invited()->create();

    $response = postPasswordSetup(adminLink($admin), 'mx7-qa2z');

    $response->assertRedirect(route('admin.dashboard'));
    expect(Hash::check('mx7-qa2z', $admin->refresh()->password))->toBeTrue();
});

it('shows the invalid page for an expired link', function () {
    $admin = User::factory()->admin()->invited()->create();
    $token = adminLink($admin);
    $this->travel(25)->hours();

    $response = $this->get(route('admin.password.setup', $token));

    $response->assertOk()->assertSee('Ez a link már nem érvényes');
});

it('does not accept a link twice', function () {
    fakeTurnstile();
    $admin = User::factory()->admin()->invited()->create();
    $token = adminLink($admin);
    postPasswordSetup($token, 'a-long-enough-password');
    $this->post(route('admin.logout'));

    $response = postPasswordSetup($token, 'another-long-password');

    $response->assertOk()->assertSee('Ez a link már nem érvényes');
});

it('confirms a reset password by e-mail', function () {
    fakeTurnstile();
    Mail::fake();
    $admin = User::factory()->admin()->withoutPassword()->create();

    postPasswordSetup(adminLink($admin, TokenPurpose::AdminPasswordReset), 'a-long-enough-password');

    Mail::assertQueued(PasswordChangedMail::class, fn (PasswordChangedMail $mail) => $mail->hasTo($admin->email));
});
