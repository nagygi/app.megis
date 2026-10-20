<?php

use App\Enums\DiagnosisRowKind;
use App\Models\DiagnosisClaim;
use App\Models\Membership;
use App\Services\Report\PublishService;
use App\Services\Report\ReportBuilder;
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

it('names no row as the biggest gap below the threshold (Extra inputok 1)', function () {
    [$setup, $main] = approvedReading();
    $row = $main->diagnosis->rows()->where('kind', DiagnosisRowKind::Value)->orderBy('position')->firstOrFail();
    $main->update(['biggest_gap_row_id' => $row->id]);

    $report = app(ReportBuilder::class)->build($main->refresh());

    expect($report['meta']['shows_experience'])->toBeFalse()
        ->and($report['biggest_gap'])->toBeNull()
        ->and(collect($report['rows'])->where('is_biggest_gap', true))->toBeEmpty();

    app(PublishService::class)->publish($main, $setup['admin']);
    actingAsCustomer($setup['leader']);
    $this->get(route('client.report.summary', [$setup['organization'], $main->diagnosis]))
        ->assertOk()
        ->assertSee('A legnagyobb rés')
        ->assertSee('Még 2 társ hangja kellett volna');
});

it('names the biggest gap once three peers have answered', function () {
    [, $main] = approvedReading();
    $row = $main->diagnosis->rows()->where('kind', DiagnosisRowKind::Value)->orderBy('position')->firstOrFail();
    $main->update(['biggest_gap_row_id' => $row->id, 'calculation' => [...$main->calculation, 'peers_answered' => 3]]);

    $report = app(ReportBuilder::class)->build($main->refresh());

    expect($report['biggest_gap']['id'])->toBe($row->id)
        ->and(collect($report['rows'])->where('is_biggest_gap', true)->pluck('id')->all())->toBe([$row->id]);
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
