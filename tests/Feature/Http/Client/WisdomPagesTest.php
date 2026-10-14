<?php

use App\Models\Area;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

it('opens the Bölcsességtár with its four topics (32 · 32.1)', function () {
    $user = customerOf();
    actingAsCustomer($user);
    $organization = $user->accessibleOrganizations()->first();

    $this->get(route('client.wisdom.index', $organization))
        ->assertOk()
        ->assertSee('Miből dolgozunk?')
        ->assertSee('A diagnózis folyamata')
        ->assertSee('A küldetésfa · a márkád alapja')
        ->assertSee('Az öt terület')
        ->assertSee('A % és a pontok');
});

it('shows a topic with "Nálatok:" and the stepper (32 · 32.2)', function () {
    $setup = diagnosisTeam();
    actingAsCustomer($setup['leader']);

    $this->get(route('client.wisdom.show', [$setup['organization'], 'kuldetesfa']))
        ->assertOk()
        ->assertSee('Értékek (gyökér)')
        ->assertSee('Nálatok:')
        ->assertSee('2 / 4 · A küldetésfa · a márkád alapja')
        ->assertSee('Következő: Az öt terület');
});

it('reads the areas from Beállítások → Területek (32 · 32.3)', function () {
    $setup = diagnosisTeam();
    Area::query()->where('key', 'strategy')->update(['trace' => 'A döntések indoklása.']);
    actingAsCustomer($setup['leader']);

    $this->get(route('client.wisdom.show', [$setup['organization'], 'teruletek']))
        ->assertOk()
        ->assertSee('Stratégia')
        ->assertSee('A döntések indoklása.');
});

it('shows the own percent to those who see the report (32 · 32.3)', function () {
    Queue::fake();
    Mail::fake();
    [$setup, $main] = publishedReport();
    actingAsCustomer($setup['leader']);

    $this->get(route('client.wisdom.show', [$setup['organization'], 'szazalek-es-pontok']))
        ->assertOk()
        ->assertSee('A ti %-otok · Belső működés');

    actingAsCustomer($setup['peers'][0]);
    $this->get(route('client.wisdom.show', [$setup['organization'], 'szazalek-es-pontok']))->assertOk()->assertDontSee('A ti %-otok');
});

it('does not know other topics', function () {
    $user = customerOf();
    actingAsCustomer($user);

    $this->get(route('client.wisdom.show', [$user->accessibleOrganizations()->first(), 'nincs-ilyen']))->assertNotFound();
});
