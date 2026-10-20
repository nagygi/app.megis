<?php

use App\Models\Organization;
use App\Models\User;

it('asks in the brand dialog before an action: Mégse keeps, Igen does it and a toast says so', function () {
    $organization = Organization::factory()->create(['name' => 'Acme Kft.']);
    $page = adminLoginInBrowser(User::factory()->admin()->create());
    $page->assertSee('Acme Kft.');

    $page->click('[aria-label="Műveletek: Acme Kft."]')->click('Inaktiválom');
    $page->assertVisible('#megis-confirm')
        ->assertSee('Biztosan inaktiválod a szervezetet?')
        ->assertNoAccessibilityIssues();

    $page->press('Mégse');
    $page->assertMissing('#megis-confirm[open]');
    expect($organization->refresh()->isActive())->toBeTrue();

    $page->click('[aria-label="Műveletek: Acme Kft."]')->click('Inaktiválom');
    $page->press('Igen');

    $page->assertSee('Mentve.')->assertNoJavaScriptErrors();
    expect($organization->refresh()->isActive())->toBeFalse();
});

it('shows the flash of a Livewire action as a toast, again after the same action', function () {
    Organization::factory()->create(['name' => 'Acme Kft.']);
    Organization::factory()->create(['name' => 'Béta Bt.']);
    $page = adminLoginInBrowser(User::factory()->admin()->create());
    $toast = '[aria-live="polite"] [x-text="toast.message"]';

    $page->click('[aria-label="Műveletek: Acme Kft."]')->click('Inaktiválom');
    $page->press('Igen');
    $page->assertVisible($toast);
    $page->click('[aria-live="polite"] [aria-label="Bezárás"]');
    $page->assertMissing($toast);

    // The same "Mentve." again: the flash element is new, so the toast comes back.
    $page->click('[aria-label="Műveletek: Béta Bt."]')->click('Inaktiválom');
    $page->press('Igen');
    $page->assertVisible($toast)->assertNoJavaScriptErrors();
});
