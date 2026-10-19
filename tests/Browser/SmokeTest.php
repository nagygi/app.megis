<?php

use App\Enums\LegalDocumentType;
use App\Mail\LoginCodeMail;
use App\Models\LegalDocumentVersion;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

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

it('saves the name on the profile as the customer leaves the field', function () {
    $organization = Organization::factory()->create();
    $customer = customerOf($organization);

    $page = loginInBrowser($customer);
    $page->navigate('/'.$organization->slug.'/profil');
    $page->fill('first_name', 'Kornélia')->keys('#first_name', 'Tab');

    $page->waitForText('Mentve')->assertNoJavaScriptErrors();
    expect($customer->refresh()->first_name)->toBe('Kornélia');
});

it('opens the menu and the user menu on a phone, and logs the customer out', function () {
    $organization = Organization::factory()->create(['name' => 'Acme Kft.']);
    $customer = customerOf($organization);

    $page = loginInBrowser($customer);
    $page->resize(390, 844);
    $page->assertPathIs('/'.$organization->slug);

    // The menu slides in from the right; an off-screen link could not be clicked.
    $page->click('[aria-controls="client-menu"]');
    $page->click('#client-menu a[href$="/ertektar"]');
    $page->assertPathIs('/'.$organization->slug.'/ertektar')->assertNoAccessibilityIssues();

    $page->click('[aria-controls="client-user-menu"]');
    $page->assertSee('Acme Kft.')->assertNoAccessibilityIssues();
    $page->click('#client-user-menu button[type="submit"]');

    $page->assertPathIs('/belepes');
});
