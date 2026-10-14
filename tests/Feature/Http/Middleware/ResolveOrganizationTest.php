<?php

use App\Models\Membership;
use App\Models\Organization;

it('opens the organization home for a member', function () {
    $organization = Organization::factory()->create(['name' => 'Acme Kft.']);
    actingAsCustomer(customerOf($organization));

    $response = $this->get(route('client.home', $organization));

    $response->assertOk()->assertSee('Acme Kft.');
});

it('sends a guest to the login page without telling whether the organization exists', function () {
    $organization = Organization::factory()->create();

    $this->get('/'.$organization->slug)->assertRedirect(route('client.login'));
    $this->get('/nincs-ilyen-szervezet')->assertRedirect(route('client.login'));
});

it('shows "Nincs jogosultságod" to a non-member', function () {
    actingAsCustomer(customerOf());
    $otherOrganization = Organization::factory()->create();

    $response = $this->get(route('client.home', $otherOrganization));

    $response->assertForbidden();
});

it('switches to another of the user’s organizations and remembers it', function () {
    $first = Organization::factory()->create();
    $second = Organization::factory()->create();
    $customer = customerOf($first);
    Membership::factory()->for($second)->for($customer)->create();
    actingAsCustomer($customer);

    $this->get(route('client.home', $second))->assertOk();

    expect($customer->fresh()->last_organization_id)->toBe($second->id);
});

it('keeps an inactive organization closed', function () {
    $organization = Organization::factory()->deactivated()->create();
    actingAsCustomer(customerOf($organization));

    $response = $this->get(route('client.home', $organization));

    $response->assertForbidden();
});

it('keeps a deactivated membership closed', function () {
    $organization = Organization::factory()->create();
    $customer = customerOf($organization);
    $customer->memberships()->update(['status' => 'inactive']);
    actingAsCustomer($customer);

    $response = $this->get(route('client.home', $organization));

    $response->assertForbidden();
});

it('lists the other organizations in the menu switcher', function () {
    $first = Organization::factory()->create(['name' => 'Elso Kft.']);
    $second = Organization::factory()->create(['name' => 'Masodik Kft.']);
    $customer = customerOf($first);
    Membership::factory()->for($second)->for($customer)->create();
    actingAsCustomer($customer);

    $response = $this->get(route('client.home', $first));

    $response->assertSee('Szervezet váltása')->assertSee('Masodik Kft.');
});
