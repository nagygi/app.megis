<?php

use App\Mail\LoginCodeMail;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;

function postCustomerLogin(array $data): TestResponse
{
    return test()->post(route('client.login.store'), [...$data, 'cf-turnstile-response' => 'turnstile-token']);
}

it('renders B1 without an avatar', function () {
    $response = $this->get(route('client.login'));

    $response->assertOk()
        ->assertSee('Belépés')
        ->assertSee('Jelszóval lépek be')
        ->assertDontSee('Menü megnyitása');
});

it('e-mails a login code to an active customer', function () {
    fakeTurnstile();
    Mail::fake();
    $customer = customerOf();

    $response = postCustomerLogin(['email' => $customer->email]);

    $response->assertRedirect(route('client.login.code'))
        ->assertSessionHas('status', 'Ha ehhez a címhez tartozik fiók, elküldtük a belépő kódot.');
    Mail::assertQueued(LoginCodeMail::class, fn (LoginCodeMail $mail) => $mail->hasTo($customer->email) && strlen($mail->code) === 6);
});

it('answers the same for an unknown or inactive address without sending anything', function (Closure $email) {
    fakeTurnstile();
    Mail::fake();

    $response = postCustomerLogin(['email' => $email()]);

    $response->assertRedirect(route('client.login.code'))
        ->assertSessionHas('status', 'Ha ehhez a címhez tartozik fiók, elküldtük a belépő kódot.');
    Mail::assertNothingQueued();
})->with([
    'unknown' => fn () => 'nobody@example.com',
    'inactive' => fn () => User::factory()->deactivated()->create()->email,
    'not registered' => fn () => User::factory()->invited()->create()->email,
]);

it('limits code requests to five per hour', function () {
    fakeTurnstile();
    Mail::fake();
    $customer = customerOf();

    foreach (range(1, 5) as $attempt) {
        postCustomerLogin(['email' => $customer->email]);
    }
    $response = postCustomerLogin(['email' => $customer->email]);

    $response->assertSessionHasErrors('email');
    Mail::assertQueued(LoginCodeMail::class, 5);
});

it('logs in with the password and lands on the organization home', function () {
    fakeTurnstile();
    $organization = Organization::factory()->create();
    $customer = customerOf($organization);
    $customer->update(['password' => Hash::make('jelszo-1234')]);

    $response = postCustomerLogin(['email' => $customer->email, 'password' => 'jelszo-1234']);

    $response->assertRedirect(route('client.home', $organization));
    $this->assertAuthenticatedAs($customer, 'web');
    expect($customer->fresh()->last_login_at)->not->toBeNull();
});

it('rejects a wrong password with the generic message', function () {
    fakeTurnstile();
    $customer = customerOf();

    $response = postCustomerLogin(['email' => $customer->email, 'password' => 'wrong-password']);

    $response->assertSessionHasErrors(['password' => 'Hibás e-mail cím vagy jelszó.']);
    $this->assertGuest('web');
});

it('pauses password login for 15 minutes after 10 wrong passwords, while the code still works', function () {
    fakeTurnstile();
    Mail::fake();
    $customer = customerOf();
    $customer->forceFill(['password' => Hash::make('jelszo-1234'), 'failed_password_attempts' => 9])->save();

    $response = postCustomerLogin(['email' => $customer->email, 'password' => 'wrong-password']);

    $response->assertSessionHasErrors(['password' => 'Túl sok hibás jelszó. 15 percig csak kóddal léphetsz be.']);
    $this->travel(5)->minutes();
    postCustomerLogin(['email' => $customer->email, 'password' => 'jelszo-1234'])->assertSessionHasErrors('password');
    postCustomerLogin(['email' => $customer->email])->assertRedirect(route('client.login.code'));
    Mail::assertQueued(LoginCodeMail::class);
});

it('redirects a logged-in customer away from the login page', function () {
    actingAsCustomer(customerOf());

    $response = $this->get(route('client.login'));

    $response->assertRedirect(route('client.landing'));
});
