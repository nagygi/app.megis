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
    Mail::fake();
    $customer = customerOf();
    $token = app(TokenService::class)->issue($customer, TokenPurpose::LoginCode)->plainToken;
    $this->travel(16)->minutes();

    $response = $this->post(route('client.login.link.resend', $token));

    $response->assertRedirect(route('client.login.code'));
    Mail::assertQueued(LoginCodeMail::class, fn (LoginCodeMail $mail) => $mail->hasTo($customer->email));
});
