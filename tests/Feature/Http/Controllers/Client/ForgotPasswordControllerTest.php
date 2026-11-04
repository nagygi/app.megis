<?php

use App\Enums\TokenPurpose;
use App\Mail\ClientPasswordResetMail;
use App\Models\Membership;
use App\Models\User;
use App\Services\Accounts\AccountDeletionService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;

function postForgotPassword(string $email): TestResponse
{
    return test()->post(route('client.password.email'), ['email' => $email, 'cf-turnstile-response' => 'turnstile-token']);
}

it('offers "Elfelejtettem a jelszavam" on B1 (14 · 14.3)', function () {
    $this->get(route('client.login'))
        ->assertOk()
        ->assertSee('Elfelejtettem a jelszavam')
        ->assertSee(route('client.password.request'));

    $this->get(route('client.password.request'))
        ->assertOk()
        ->assertSee('Elfelejtett jelszó')
        ->assertSee('Küldöm a linket');
});

it('e-mails a 60-minute password link to a customer, with or without a password', function (bool $withPassword) {
    fakeTurnstile();
    Mail::fake();
    $customer = customerOf();
    if (! $withPassword) {
        $customer->forceFill(['password' => null])->save();
    }

    $response = postForgotPassword($customer->email);

    $response->assertRedirect(route('client.login'))
        ->assertSessionHas('status', 'Ha ehhez a címhez tartozik fiók, elküldtük a jelszó-beállító linket. A link 60 percig él.');
    Mail::assertQueued(ClientPasswordResetMail::class, fn (ClientPasswordResetMail $mail) => $mail->hasTo($customer->email) && ! $mail->byAdmin);

    $token = $customer->tokens()->sole();
    expect($token->purpose)->toBe(TokenPurpose::ClientPasswordReset)
        ->and((int) round(now()->diffInMinutes($token->expires_at)))->toBe(60);
})->with(['with a password' => true, 'without a password' => false]);

it('answers the same for every other address without sending anything', function (Closure $email) {
    fakeTurnstile();
    Mail::fake();

    $response = postForgotPassword($email());

    $response->assertRedirect(route('client.login'))
        ->assertSessionHas('status', 'Ha ehhez a címhez tartozik fiók, elküldtük a jelszó-beállító linket. A link 60 percig él.');
    Mail::assertNothingQueued();
})->with([
    'unknown' => fn () => 'nobody@example.com',
    'inactive' => fn () => User::factory()->deactivated()->create()->email,
    'not registered' => fn () => User::factory()->invited()->create()->email,
    'admin' => fn () => tap(User::factory()->admin()->create(), fn (User $admin) => Membership::factory()->for($admin)->create())->email,
    'archived' => fn () => tap(customerOf(), fn (User $customer) => app(AccountDeletionService::class)->request($customer))->email,
]);

it('limits link requests to five per hour', function () {
    fakeTurnstile();
    Mail::fake();
    $customer = customerOf();

    foreach (range(1, 5) as $attempt) {
        postForgotPassword($customer->email);
    }
    $response = postForgotPassword($customer->email);

    $response->assertSessionHasErrors('email');
    Mail::assertQueued(ClientPasswordResetMail::class, 5);
});

it('needs Turnstile', function () {
    fakeTurnstile(success: false);
    Mail::fake();
    $customer = customerOf();

    postForgotPassword($customer->email)->assertSessionHasErrors('cf-turnstile-response');

    Mail::assertNothingQueued();
});

it('invalidates the earlier link with a new request', function () {
    fakeTurnstile();
    Mail::fake();
    $customer = customerOf();

    postForgotPassword($customer->email);
    postForgotPassword($customer->email);

    expect($customer->tokens()->pending()->count())->toBe(1)
        ->and($customer->tokens()->count())->toBe(2);
});
