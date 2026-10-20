<?php

use App\Models\Organization;
use App\Models\User;

it('keeps the closed phone menu out of reach, holds the focus in the open one and closes it with Esc', function () {
    $organization = Organization::factory()->create();
    $page = loginInBrowser(customerOf($organization));
    $page->resize(390, 844);
    $page->navigate('/'.$organization->slug);
    $menuVisibility = '() => getComputedStyle(document.getElementById("client-menu")).visibility';

    expect($page->script($menuVisibility))->toBe('hidden');

    $page->click('[aria-controls="client-menu"]')->wait(0.3);
    expect($page->script($menuVisibility))->toBe('visible')
        ->and($page->script('() => document.activeElement.closest("#client-menu") !== null'))->toBeTrue();

    $page->keys('#client-menu', 'Escape')->wait(0.3);
    expect($page->script($menuVisibility))->toBe('hidden')
        ->and($page->script('() => document.activeElement.getAttribute("aria-controls")'))->toBe('client-menu');
    $page->assertNoJavaScriptErrors();
});

it('leads home from the menu and the libraries', function () {
    $organization = Organization::factory()->create();
    $page = loginInBrowser(customerOf($organization));
    $page->navigate(route('client.values', $organization, false));

    $page->assertSee('Kezdőlap')->click('header a[href$="/'.$organization->slug.'"]');
    $page->assertPathIs('/'.$organization->slug)->assertNoJavaScriptErrors();
});

it('closes the admin drawer with Esc and keeps it out of reach when closed', function () {
    $page = adminLoginInBrowser(User::factory()->admin()->create());
    // Loaded again at the new size: measuring right after the resize could still see the desktop layout.
    $page->resize(390, 844);
    $page->navigate('/admin/szervezetek');
    $navVisibility = '() => getComputedStyle(document.getElementById("admin-nav")).visibility';

    expect($page->script($navVisibility))->toBe('hidden');

    $page->click('[aria-controls="admin-nav"]')->wait(0.3);
    expect($page->script($navVisibility))->toBe('visible');

    $page->keys('#admin-nav', 'Escape')->wait(0.3);
    expect($page->script($navVisibility))->toBe('hidden');
    $page->assertNoJavaScriptErrors();
});
