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
        ->assertSee('Itt fogod látni a(z) Acme Kft. diagnózisát, amint elindul.')
        // A member's way ahead: the peer's (26 · 26.4).
        ->assertSee('A csapat hangja')
        ->assertSee('Amikor a vezetőd elindítja a diagnózist, meghívót kapsz.')
        ->assertSee('Útravaló')
        ->assertDontSee('ndulás:')
        ->assertDontSee('A vezető útja');
});

it('shows the leader of a draft diagnosis their steps and the team\'s, not clickable (26 · 26.4)', function () {
    $setup = diagnosisTeam(peers: 2);
    $main = startMainDiagnosis($setup, invite: false);
    $setup['peers'][0]->update(['first_name' => 'Bea', 'last_name' => 'Társ']);
    actingAsCustomer($setup['leader']);

    $this->get(route('client.home', $setup['organization']))
        ->assertOk()
        ->assertSee('amint elindul')
        ->assertSee('A vezető útja')
        ->assertSee('Saját értékek')
        ->assertSee('A csapat hangja · '.$setup['team']->name)
        ->assertSee($setup['peers'][0]->name)
        ->assertSee('Meghívó')
        ->assertSee('indulás: '.today()->addWeek()->format('m.d.'))
        ->assertSee('A csapat az induláskor ('.today()->addWeek()->format('m.d.').') kap meghívót.')
        ->assertDontSeeHtml('href="'.route('client.diagnosis.sajat-ertekek', [$setup['organization'], $main->diagnosis]).'"');
});

it('shows an organization leader without a role the leader\'s way, with the team being set up', function () {
    $setup = diagnosisTeam(peers: 2, organizationLeader: false);
    startMainDiagnosis($setup, invite: false);
    $organizationLeader = User::factory()->create();
    Membership::factory()->for($setup['organization'])->for($organizationLeader)->create(['is_organization_leader' => true]);
    actingAsCustomer($organizationLeader);

    $this->get(route('client.home', $setup['organization']))
        ->assertOk()
        ->assertSee('A vezető útja')
        ->assertSee('A csapat hangja · '.$setup['team']->name)
        ->assertSee($setup['peers'][1]->name)
        ->assertSee('indulás: '.today()->addWeek()->format('m.d.'));
});

it('tells an organization leader when the diagnosis someone else was invited to lead starts', function () {
    $setup = diagnosisTeam(peers: 2, organizationLeader: false);
    startMainDiagnosis($setup);
    $organizationLeader = User::factory()->create();
    Membership::factory()->for($setup['organization'])->for($organizationLeader)->create(['is_organization_leader' => true]);
    actingAsCustomer($organizationLeader);

    $this->get(route('client.home', $setup['organization']))
        ->assertOk()
        ->assertSee('Jelenleg nincs olyan diagnózis, amelyben részt veszel.')
        ->assertSee('indulás: '.today()->addWeek()->format('m.d.'));
});

it('says the start day is being agreed when the diagnosis has none', function () {
    $setup = diagnosisTeam(peers: 2);
    startMainDiagnosis($setup, invite: false, overrides: ['starts_on' => null]);
    actingAsCustomer($setup['leader']);

    $this->get(route('client.home', $setup['organization']))
        ->assertOk()
        ->assertSee('indulás: egyeztetés alatt')
        ->assertSee('A csapat az induláskor kap meghívót.');
});

it('tells a peer of a draft diagnosis who will invite them, with the peer\'s way', function () {
    $setup = diagnosisTeam(peers: 1);
    startMainDiagnosis($setup, invite: false);
    actingAsCustomer($setup['peers'][0]);

    $this->get(route('client.home', $setup['organization']))
        ->assertOk()
        ->assertSee('amint elindul')
        ->assertSee($setup['leader']->name.' hamarosan meghív a(z) '.$setup['team']->name.' diagnózisába.')
        ->assertSee('Indulás: '.today()->addWeek()->format('m.d.'))
        ->assertDontSee('Indulás: '.today()->addWeek()->format('m.d.').'.')
        ->assertSee('Hozzáteszel')
        ->assertDontSee('A vezető útja');
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
