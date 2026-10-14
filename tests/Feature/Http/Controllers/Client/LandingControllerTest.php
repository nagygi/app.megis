<?php

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;

it('sends the customer to the last used organization', function () {
    $first = Organization::factory()->create(['name' => 'A Kft.']);
    $second = Organization::factory()->create(['name' => 'B Kft.']);
    $customer = customerOf($first);
    Membership::factory()->for($second)->for($customer)->create();
    $customer->forceFill(['last_organization_id' => $second->id])->save();
    actingAsCustomer($customer);

    $response = $this->get(route('client.landing'));

    $response->assertRedirect(route('client.home', $second));
});

it('shows the paused page when no organization is active', function () {
    $customer = User::factory()->create();
    Membership::factory()->inactive()->for($customer)->create();
    actingAsCustomer($customer);

    $this->get(route('client.landing'))->assertRedirect(route('client.paused'));
    $this->get(route('client.paused'))->assertOk()->assertSee('A hozzáférésed szünetel. Keresd a mégis kapcsolattartódat.');
});

it('sends guests to the customer login', function () {
    $response = $this->get(route('client.landing'));

    $response->assertRedirect(route('client.login'));
});

it('logs the customer out after a password change elsewhere', function () {
    $organization = Organization::factory()->create();
    $customer = actingAsCustomer(customerOf($organization));
    $customer->invalidateSessions();

    $response = $this->get(route('client.home', $organization));

    $response->assertRedirect(route('client.login'));
    $this->assertGuest('web');
});
