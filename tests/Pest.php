<?php

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Services\Auth\GuardSession;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature');

// Browser tests load the built assets, so layout and contrast are checked for real (npm run build first).
pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->beforeEach(fn () => $this->withVite())
    ->in('Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Logs an admin in on the admin guard the way a real login does: the session
 * carries the current auth epoch and a fresh activity time.
 */
function actingAsAdmin(?User $admin = null): User
{
    $admin ??= User::factory()->admin()->create();

    test()->actingAs($admin, GuardSession::ADMIN)->withSession([
        'auth_epoch.'.GuardSession::ADMIN => $admin->auth_epoch,
        GuardSession::ADMIN_LAST_ACTIVITY_KEY => now()->getTimestamp(),
    ]);

    return $admin;
}

/**
 * Logs a customer in on the customer guard, with the session's auth epoch.
 */
function actingAsCustomer(User $customer): User
{
    test()->actingAs($customer, GuardSession::WEB)->withSession([
        'auth_epoch.'.GuardSession::WEB => $customer->auth_epoch,
    ]);

    return $customer;
}

/**
 * An activated customer with an active membership in an active organization.
 */
function customerOf(?Organization $organization = null): User
{
    $organization ??= Organization::factory()->create();
    $customer = User::factory()->create();

    Membership::factory()->for($organization)->for($customer)->create();

    return $customer;
}

/**
 * Cloudflare's verification answers with the given result.
 */
function fakeTurnstile(bool $success = true): void
{
    Http::fake([
        'https://challenges.cloudflare.com/*' => Http::response(['success' => $success]),
        'https://api.pwnedpasswords.com/*' => Http::response(''),
    ]);
}

/**
 * Browser tests: fills the field the Turnstile widget would add, so the flow
 * does not depend on Cloudflare being reachable; the server check is faked.
 */
function passTurnstile(mixed $page): void
{
    $page->script(<<<'JS'
        () => document.querySelector('form').insertAdjacentHTML(
            'beforeend',
            '<input type="hidden" name="cf-turnstile-response" value="test-token">'
        )
    JS);
}
