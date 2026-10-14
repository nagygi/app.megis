<?php

use App\Enums\LegalDocumentType;
use App\Mail\LoginCodeMail;
use App\Models\LegalDocumentVersion;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Fills the field the Turnstile widget would add, so the flow does not depend
 * on Cloudflare being reachable; the server side check is faked.
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

it('shows the public home with the legal links', function () {
    LegalDocumentVersion::factory()->ofType(LegalDocumentType::Terms)->create();

    $page = visit('/');

    $page->assertSee('ÁSZF')
        ->assertNoJavaScriptErrors()
        ->assertNoAccessibilityIssues();
});

it('logs an admin in to the organizations list', function () {
    fakeTurnstile();
    User::factory()->admin()->create(['email' => 'admin@megis.hu', 'password' => Hash::make('a-long-enough-password')]);

    $page = visit('/admin/belepes');
    $page->assertNoAccessibilityIssues()
        ->fill('email', 'admin@megis.hu')
        ->fill('password', 'a-long-enough-password');
    passTurnstile($page);
    $page->press('Belépek');

    $page->assertPathIs('/admin/szervezetek')
        ->assertSee('Szervezetek')
        ->assertNoAccessibilityIssues();
});

it('logs a customer in with an e-mailed code', function () {
    fakeTurnstile();
    Mail::fake();
    $organization = Organization::factory()->create(['name' => 'Acme Kft.']);
    $customer = customerOf($organization);

    $page = visit('/belepes');
    $page->assertNoAccessibilityIssues()->fill('email', $customer->email);
    passTurnstile($page);
    $page->press('Belépek');

    $page->assertPathIs('/belepes/kod')->assertNoAccessibilityIssues();
    $code = Mail::queued(LoginCodeMail::class)->sole()->code;
    $page->fill('code', $code)->press('Belépek');

    $page->assertPathIs('/'.$organization->slug)
        ->assertSee('Acme Kft.')
        ->assertNoAccessibilityIssues();
});
