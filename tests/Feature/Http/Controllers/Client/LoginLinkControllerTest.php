<?php

use App\Enums\TokenPurpose;
use App\Mail\LoginCodeMail;
use App\Models\Organization;
use App\Services\Auth\TokenService;
use Illuminate\Support\Facades\Mail;

it('only shows a button when the link is opened, so mail scanners do not use it up', function () {
    $customer = customerOf();
    $token = app(TokenService::class)->issue($customer, TokenPurpose::LoginCode)->plainToken;

    $response = $this->get(route('client.login.link', $token));

    $response->assertOk()->assertSee('Belépek');
    expect($customer->tokens()->sole()->consumed_at)->toBeNull();
    $this->assertGuest('web');
});

it('logs in when the button is pressed', function () {
    $organization = Organization::factory()->create();
    $customer = customerOf($organization);
    $token = app(TokenService::class)->issue($customer, TokenPurpose::LoginCode)->plainToken;

    $response = $this->post(route('client.login.link.store', $token));

    $response->assertRedirect(route('client.home', $organization));
    $this->assertAuthenticatedAs($customer, 'web');
});

it('shows "Ez a link már lejárt" for a used link', function () {
    $customer = customerOf();
    $token = app(TokenService::class)->issue($customer, TokenPurpose::LoginCode)->plainToken;
    $this->post(route('client.login.link.store', $token));
    $this->post(route('client.logout'));

    $response = $this->get(route('client.login.link', $token));

    $response->assertOk()->assertSee('Ez a link már lejárt')->assertSee('Újat kérek');
});

it('sends a fresh code from an expired link', function () {
    fakeTurnstile();
    Mail::fake();
    $customer = customerOf();
    $token = app(TokenService::class)->issue($customer, TokenPurpose::LoginCode)->plainToken;
    $this->travel(16)->minutes();

    $response = $this->post(route('client.login.link.resend', $token), ['cf-turnstile-response' => 'turnstile-token']);

    $response->assertRedirect(route('client.login.code'));
    Mail::assertQueued(LoginCodeMail::class, fn (LoginCodeMail $mail) => $mail->hasTo($customer->email));
});

it('asks for Turnstile before a fresh code from an expired link (14 · 14.2)', function () {
    fakeTurnstile(false);
    Mail::fake();
    $token = app(TokenService::class)->issue(customerOf(), TokenPurpose::LoginCode)->plainToken;
    $this->travel(16)->minutes();

    $response = $this->post(route('client.login.link.resend', $token), ['cf-turnstile-response' => 'turnstile-token']);

    $response->assertSessionHasErrors('cf-turnstile-response');
    Mail::assertNothingQueued();
});

it('limits fresh codes from an expired link to five per hour, like B1 (14 · 14.2)', function () {
    fakeTurnstile();
    Mail::fake();
    $token = app(TokenService::class)->issue(customerOf(), TokenPurpose::LoginCode)->plainToken;

    foreach (range(1, 5) as $attempt) {
        $this->travel(61)->seconds();
        $this->post(route('client.login.link.resend', $token), ['cf-turnstile-response' => 'turnstile-token'])->assertRedirect(route('client.login.code'));
    }
    $this->travel(61)->seconds();
    $response = $this->post(route('client.login.link.resend', $token), ['cf-turnstile-response' => 'turnstile-token']);

    $response->assertSessionHasErrors('cf-turnstile-response');
    Mail::assertQueued(LoginCodeMail::class, 5);
});
