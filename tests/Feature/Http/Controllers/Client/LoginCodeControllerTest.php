<?php

use App\Enums\TokenPurpose;
use App\Mail\LoginCodeMail;
use App\Models\Organization;
use App\Models\User;
use App\Services\Auth\TokenService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;

function issueLoginCode(User $user): string
{
    return (string) app(TokenService::class)->issue($user, TokenPurpose::LoginCode)->plainCode;
}

function postLoginCode(User $user, string $code): TestResponse
{
    return test()->withSession(['login.email' => $user->email])->post(route('client.login.code.store'), ['code' => $code]);
}

it('logs in with the e-mailed code and lands on the organization home', function () {
    $organization = Organization::factory()->create();
    $customer = customerOf($organization);
    $code = issueLoginCode($customer);

    $response = postLoginCode($customer, $code);

    $response->assertRedirect(route('client.home', $organization));
    $this->assertAuthenticatedAs($customer, 'web');
    expect($customer->tokens()->sole()->consumed_at)->not->toBeNull();
});

it('rejects a wrong code', function () {
    $customer = customerOf();
    issueLoginCode($customer);

    $response = postLoginCode($customer, '000000');

    $response->assertSessionHasErrors(['code' => 'Hibás vagy lejárt kód. Ellenőrizd, vagy kérj újat.']);
    $this->assertGuest('web');
});

it('invalidates the code after five wrong attempts', function () {
    $customer = customerOf();
    $code = issueLoginCode($customer);
    $wrongCode = $code === '111111' ? '222222' : '111111';

    foreach (range(1, 5) as $attempt) {
        $this->travel(61)->seconds();
        postLoginCode($customer, $wrongCode);
    }
    $this->travel(61)->seconds();
    $response = postLoginCode($customer, $code);

    $response->assertSessionHasErrors('code');
    $this->assertGuest('web');
});

it('rejects a code older than 15 minutes', function () {
    $customer = customerOf();
    $code = issueLoginCode($customer);
    $this->travel(16)->minutes();

    $response = postLoginCode($customer, $code);

    $response->assertSessionHasErrors('code');
});

it('lets a new code be requested only after 60 seconds', function () {
    Mail::fake();
    $customer = customerOf();
    issueLoginCode($customer);

    $this->withSession(['login.email' => $customer->email])->post(route('client.login.code.resend'))->assertSessionHasErrors('code');
    $this->travel(61)->seconds();
    $this->withSession(['login.email' => $customer->email])->post(route('client.login.code.resend'))->assertRedirect(route('client.login.code'));

    Mail::assertQueued(LoginCodeMail::class, 1);
});

it('shows that the code is on its way on B2', function () {
    $customer = customerOf();

    $response = $this->withSession(['login.email' => $customer->email, 'status' => 'Elküldtük a belépő kódot.'])->get(route('client.login.code'));

    $response->assertOk()->assertSee('Elküldtük a belépő kódot.');
});

it('sends visitors without a pending e-mail back to B1', function () {
    $response = $this->get(route('client.login.code'));

    $response->assertRedirect(route('client.login'));
});
