<?php

use App\Enums\DiagnosisRowKind;
use App\Models\DiagnosisClaim;
use App\Models\Membership;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

it('renders every page of the report to the leader (27)', function () {
    [$setup, $main] = publishedReport();
    actingAsCustomer($setup['leader']);
    $parameters = [$setup['organization'], $main->diagnosis];
    $value = $main->diagnosis->rows()->where('kind', DiagnosisRowKind::Value)->orderBy('position')->first();

    $this->get(route('client.report.show', $parameters))->assertOk()->assertSee('Mennyire lesz nálatok az értékekből tett?')->assertSee('mérőpont');
    $this->get(route('client.report.summary', $parameters))->assertOk()->assertSee('A legnagyobb rés')->assertSee('Hol kezdenénk');
    $this->get(route('client.report.internal', $parameters))->assertOk()->assertSee('Erősség');
    $this->get(route('client.report.chapter', [...$parameters, 'ertekek']))->assertOk()->assertSee('Nálatok szólnak a csúszásról.')->assertSee('Magatokra ismertek?')->assertSee('Még 2 társ hangja kellett volna');
    $this->get(route('client.report.item', [...$parameters, 'ertekek', $value->id]))->assertOk()->assertSee('A ti definíciótok')->assertSee('Szóltam a csúszásról');
    $this->get(route('client.report.chapter', [...$parameters, 'folyamatok']))->assertOk()->assertSee('Retró')->assertSee('2 alkalom');
    $this->get(route('client.report.chapter', [...$parameters, 'teruletek']))->assertOk()->assertSee('Stratégia');
    $this->get(route('client.report.chapter', [...$parameters, 'markad-alapja']))->assertOk()->assertSee('A mégis olvasata');
    $this->get(route('client.report.compare', $parameters))->assertOk()->assertSee('Te');
    $this->get(route('client.report.closing', $parameters))->assertOk()->assertSee('Ez a ti diagnózisotok.');
});

it('shows the report to the organization\'s leaders but never to a peer (27 · 27.1)', function () {
    [$setup, $main] = publishedReport();
    $parameters = [$setup['organization'], $main->diagnosis];

    actingAsCustomer($setup['peers'][0]);
    $this->get(route('client.report.show', $parameters))->assertForbidden();

    $orgLeader = customerOf($setup['organization']);
    Membership::query()->where('user_id', $orgLeader->id)->update(['is_organization_leader' => true]);
    actingAsCustomer($orgLeader);
    $this->get(route('client.report.show', $parameters))->assertOk();
});

it('keeps the report closed before publishing', function () {
    [$setup, $main] = runningDiagnosis();
    actingAsCustomer($setup['leader']);

    $this->get(route('client.report.show', [$setup['organization'], $main->diagnosis]))->assertForbidden();
});

it('reads the frozen snapshot, not the live rows (21 · 21.3)', function () {
    [$setup, $main] = publishedReport();
    DiagnosisClaim::query()->update(['text' => 'Utólag átírt mondat.']);
    actingAsCustomer($setup['leader']);

    $this->get(route('client.report.chapter', [$setup['organization'], $main->diagnosis, 'ertekek']))
        ->assertSee('Nálatok szólnak a csúszásról.')
        ->assertDontSee('Utólag átírt mondat.');
});

it('switches to the individual and the organization view (27 · 27.9)', function () {
    [$setup, $main] = publishedReport();
    actingAsCustomer($setup['leader']);
    $url = route('client.report.chapter', [$setup['organization'], $main->diagnosis, 'ertekek']);

    $this->get($url.'?nezet=egyen')->assertOk()->assertSee('a te anyagaidból');
    $this->get($url.'?nezet=szervezet')->assertOk()->assertSee('a szervezet publikált diagnózisaiból');
});
