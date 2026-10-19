<?php

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('greets the customer until a diagnosis of the organization starts', function () {
    $organization = Organization::factory()->create(['name' => 'Acme Kft.']);
    actingAsCustomer(customerOf($organization));

    $this->get(route('client.home', $organization))
        ->assertOk()
        ->assertSee('Itt fogod látni a(z) Acme Kft. diagnózisát, amint elindul.');
});

it('tells an organization leader who is not in the running diagnosis that they take part in none', function () {
    Queue::fake();
    [$setup] = runningDiagnosis();
    $organizationLeader = User::factory()->create();
    Membership::factory()->for($setup['organization'])->for($organizationLeader)->create(['is_organization_leader' => true]);
    actingAsCustomer($organizationLeader);

    $this->get(route('client.home', $setup['organization']))
        ->assertOk()
        ->assertSee('Jelenleg nincs olyan diagnózis, amelyben részt veszel.')
        ->assertDontSee('amint elindul');
});

it('keeps "amint elindul" for a peer whose diagnosis does not run yet', function () {
    $setup = diagnosisTeam();
    startMainDiagnosis($setup);
    actingAsCustomer($setup['peers'][0]);

    // Peers are invited when it runs (25 · 25.1).
    $this->get(route('client.home', $setup['organization']))
        ->assertOk()
        ->assertSee('amint elindul');
});

it('opens the user menu from the avatar and the menu from the round button', function () {
    $organization = Organization::factory()->create(['name' => 'Acme Kft.']);
    $customer = User::factory()->create(['first_name' => 'Alma', 'last_name' => 'Nagy']);
    Membership::factory()->for($organization)->for($customer)->create(['position' => 'ügyvezető']);
    actingAsCustomer($customer);

    $this->get(route('client.home', $organization))
        ->assertOk()
        ->assertSee('Felhasználói menü')
        ->assertSee('Acme Kft. · ügyvezető')
        ->assertSeeHtml('href="'.route('client.profile', $organization).'"')
        ->assertSeeHtml('action="'.route('client.logout').'"')
        ->assertSee('Menü megnyitása');
});
