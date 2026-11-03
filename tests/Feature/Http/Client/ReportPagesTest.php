<?php

use App\Enums\DiagnosisRowKind;
use App\Enums\MainDiagnosisSubject;
use App\Models\Consent;
use App\Models\DiagnosisClaim;
use App\Models\DiagnosisText;
use App\Models\EvidenceTrace;
use App\Models\Membership;
use App\Models\Process;
use App\Models\Product;
use App\Services\Diagnoses\ReadingService;
use App\Services\Report\HungarianNumber;
use App\Services\Report\PublishService;
use App\Services\Report\ReportBuilder;
use App\Services\Report\ReportText;
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

it('links the chapter dots and the footer page by page, and marks what is coming (prototype R0–R8)', function () {
    [$setup, $main] = publishedReport();
    actingAsCustomer($setup['leader']);
    $parameters = [$setup['organization'], $main->diagnosis];
    $values = $main->diagnosis->rows()->where('kind', DiagnosisRowKind::Value)->get();
    [$first, $second] = app(ReportBuilder::class)->build($main)['chapters']['values']['rows'];

    $this->get(route('client.report.show', $parameters))->assertOk()
        ->assertSee('0 / 8 · A válasz')
        ->assertSee('href="'.route('client.report.summary', $parameters).'"', false)
        ->assertSee('href="'.route('client.report.chapter', [...$parameters, 'ertekek']).'"', false)
        ->assertSee('Brand riport · hamarosan')
        ->assertSee('Bridge to Purpose · hamarosan')
        ->assertSee('Képesség');
    $this->get(route('client.report.chapter', [...$parameters, 'ertekek']))->assertOk()
        ->assertSee('2 / 8 · Értékek')
        ->assertSee('href="'.route('client.report.chapter', [...$parameters, 'folyamatok']).'"', false);
    $this->get(route('client.report.item', [...$parameters, 'ertekek', $first['id']]))->assertOk()
        ->assertSee($first['label'].' · 1 / '.$values->count().' érték')
        ->assertSee('href="'.route('client.report.item', [...$parameters, 'ertekek', $second['id']]).'"', false);
    $this->get(route('client.report.chapter', [...$parameters, 'markad-alapja']))->assertOk()
        ->assertSee('href="'.route('client.report.closing', $parameters).'"', false);
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

it('tells what we look at in a routine, with its occasions (18 · 18.2, prototype R3·b)', function () {
    [$setup, $main] = publishedReport();
    actingAsCustomer($setup['leader']);
    $process = $main->diagnosis->rows()->where('kind', DiagnosisRowKind::Process)->sole();

    $this->get(route('client.report.item', [$setup['organization'], $main->diagnosis, 'folyamatok', $process->id]))
        ->assertOk()
        ->assertSee('Mit nézünk ebben a rutinban')
        ->assertSee('A '.DiagnosisText::processKey($process->id).' mondata.')
        ->assertSee('2 alkalom '.now()->subDays(2)->format('m.d').'-ig: eredményes 0, részben 0, nem 0.');
});

it('tells what a hunch rests on, by source (18 · 18.3)', function () {
    [$setup, $main] = approvedReading();
    $row = $main->diagnosis->rows()->where('kind', DiagnosisRowKind::Value)->orderBy('position')->skip(1)->firstOrFail();
    $traceIds = collect([['recording', 'Az első felvétel.'], ['recording', 'A második felvétel.'], ['note', 'A jegyzet.']])
        ->map(fn (array $trace) => EvidenceTrace::create(['diagnosis_row_id' => $row->id, 'quote' => $trace[1], 'source_type' => $trace[0], 'role' => 'leader', 'recorded_on' => today()])->id)
        ->all();
    $reading = app(ReadingService::class);
    $reading->approveClaim($reading->saveClaim($main, null, ['diagnosis_row_id' => $row->id, 'kind' => 'hunch', 'text' => 'Talán itt is szóltok.', 'trace_ids' => $traceIds], $setup['admin']), $setup['admin']);
    app(PublishService::class)->publish($main->refresh(), $setup['admin']);
    actingAsCustomer($setup['leader']);

    $this->get(route('client.report.item', [$setup['organization'], $main->diagnosis, 'ertekek', $row->id]))
        ->assertOk()
        ->assertSee('Talán itt is szóltok.')
        ->assertSee('Kevés még a nyom: 2 felvétel, 1 jegyzet.');
});

it('greys the team view below the threshold, even as the default view (18 · 18.6)', function () {
    [$setup, $main] = publishedReport();
    actingAsCustomer($setup['leader']);

    $response = $this->get(route('client.report.chapter', [$setup['organization'], $main->diagnosis, 'ertekek']))
        ->assertOk()
        ->assertSee('Még 2 társ hangja kellett volna');

    expect($response->getContent())->toMatch('/<span[^>]*aria-current="page"[^>]*aria-disabled="true"[^>]*>Csapat/');
});

it('gives a private person no view switcher and no comparison (18 · 18.5)', function () {
    [$setup, $main] = approvedReading();
    $main->update(['subject' => MainDiagnosisSubject::Individual]);
    app(PublishService::class)->publish($main->refresh(), $setup['admin']);
    actingAsCustomer($setup['leader']);
    $parameters = [$setup['organization'], $main->diagnosis];

    $this->get(route('client.report.chapter', [...$parameters, 'ertekek']).'?nezet=egyen')
        ->assertOk()
        ->assertSee('Nálatok szólnak a csúszásról.')
        ->assertDontSee('aria-label="Nézet"', false)
        ->assertDontSee('a te anyagaidból')
        ->assertDontSee(route('client.report.compare', $parameters));
    $this->get(route('client.report.compare', $parameters))->assertNotFound();
});

it('shows a peer quote to the leader only with the A1 switch and the peer consent (31 · 31.6, 17 · 17.2)', function () {
    [$setup, $main] = approvedReading();
    $peer = $setup['peers'][0];
    $row = $main->diagnosis->rows()->where('kind', DiagnosisRowKind::Value)->orderBy('position')->first();
    EvidenceTrace::create(['diagnosis_row_id' => $row->id, 'quote' => 'A társ idézete.', 'source_type' => 'mirror', 'role' => 'peer', 'user_id' => $peer->id, 'recorded_on' => today()]);
    EvidenceTrace::create(['diagnosis_row_id' => $row->id, 'quote' => 'Kézzel felvett társ-nyom.', 'source_type' => 'note', 'role' => 'peer', 'recorded_on' => today(), 'created_by' => $setup['admin']->id]);
    $quotes = fn () => collect(app(ReportBuilder::class)->build($main->refresh())['rows'])->flatMap(fn (array $row) => $row['traces'])->pluck('quote');

    expect($quotes())->toContain('Szóltam a csúszásról, mielőtt kérdezték volna.', 'Kézzel felvett társ-nyom.')
        ->not->toContain('A társ idézete.');

    $consent = Consent::create(['user_id' => $peer->id, 'main_diagnosis_id' => $main->id, 'kind' => 'quotes', 'text_version' => 'v', 'text' => 't', 'given_at' => now()]);
    expect($quotes())->toContain('A társ idézete.');

    $consent->update(['revoked_at' => now()]);
    expect($quotes())->not->toContain('A társ idézete.');

    $consent->update(['revoked_at' => null]);
    $main->update(['show_peer_quotes' => false]);
    expect($quotes())->not->toContain('A társ idézete.', 'Kézzel felvett társ-nyom.')
        ->toContain('Szóltam a csúszásról, mielőtt kérdezték volna.');
});

it('follows the view switcher on the deep pages too (27 · 27.9)', function () {
    [$setup, $main] = publishedReport();
    actingAsCustomer($setup['leader']);
    $value = $main->diagnosis->rows()->where('kind', DiagnosisRowKind::Value)->orderBy('position')->first()->load('value');
    $url = route('client.report.item', [$setup['organization'], $main->diagnosis, 'ertekek', $value->id]);
    // The leader brought one material: too few for their own view (ViewDataBuilder::INDIVIDUAL_MINIMUM).
    $individual = __('report.question.item', ['score' => __('report.insufficient'), 'label' => $value->label()]);

    $this->get($url)->assertOk()->assertDontSee($individual);
    $this->get($url.'?nezet=egyen')->assertOk()->assertSee($individual)->assertSee('a te anyagaidból');
    $this->get($url.'?nezet=szervezet')->assertOk()->assertDontSee($individual)->assertSee('a szervezet publikált diagnózisaiból');
});

it('gives a process its experience after the occasions and its gap on R3·b (27 · 27.6)', function () {
    [, $main] = approvedReading();
    $main->update(['calculation' => [...$main->calculation, 'peers_answered' => 3]]);
    $process = $main->diagnosis->rows()->where('kind', DiagnosisRowKind::Process)->sole();
    $process->update(['experience' => 5]);

    $report = app(ReportBuilder::class)->build($main->refresh());
    $row = collect($report['chapters']['processes']['rows'])->sole();

    expect($row['experience_whole'])->toBe(5)
        ->and(ReportText::gap($row, $report['meta']))->toBe(__('report.gap_sentence', ['experience' => HungarianNumber::dative(5), 'score' => HungarianNumber::accusative($row['score_whole'])]));
});

it('lists the processes not measured apart, without a point (27 · 27.6)', function () {
    [$setup, $main] = approvedReading();
    Process::factory()->accepted()->for($setup['organization'])->create(['name' => 'Negyedéves tervezés']);
    app(PublishService::class)->publish($main, $setup['admin']);
    actingAsCustomer($setup['leader']);

    $this->get(route('client.report.chapter', [$setup['organization'], $main->diagnosis, 'folyamatok']))
        ->assertOk()
        ->assertSeeInOrder(['Nem mért folyamatok', 'Negyedéves tervezés']);
});

it('drops the processes chapter when the leader skipped them, keeping the count (27 · 27.6)', function () {
    [$setup, $main] = approvedReading();
    $main->diagnosis->rows()->where('kind', DiagnosisRowKind::Process)->delete();
    app(PublishService::class)->publish($main->refresh(), $setup['admin']);
    actingAsCustomer($setup['leader']);
    $parameters = [$setup['organization'], $main->diagnosis];

    $this->get(route('client.report.show', $parameters))->assertOk()->assertDontSee('href="'.route('client.report.chapter', [...$parameters, 'folyamatok']).'"', false);
    $this->get(route('client.report.chapter', [...$parameters, 'ertekek']))->assertOk()
        ->assertSee('2 / 8 · Értékek')
        ->assertSee('href="'.route('client.report.chapter', [...$parameters, 'teruletek']).'"', false);
    $this->get(route('client.report.chapter', [...$parameters, 'folyamatok']))->assertNotFound();
});

it('shows the chapter dots on the deep pages and links the evidence of R1·b (27 · 27.1)', function () {
    [$setup, $main] = publishedReport();
    actingAsCustomer($setup['leader']);
    $parameters = [$setup['organization'], $main->diagnosis];
    $strength = EvidenceTrace::query()->firstOrFail()->diagnosis_row_id;
    $value = $main->diagnosis->rows()->where('kind', DiagnosisRowKind::Value)->orderBy('position')->first();

    $this->get(route('client.report.item', [...$parameters, 'ertekek', $value->id]))->assertOk()
        ->assertSee('href="'.route('client.report.chapter', [...$parameters, 'folyamatok']).'"', false);
    $this->get(route('client.report.internal', $parameters))->assertOk()
        ->assertSee('href="'.route('client.report.item', [...$parameters, 'ertekek', $strength]).'"', false);
});

it('shows the snapshot on one page, with the yellow proposal and the evidence link (27 · 27.10)', function () {
    [$setup, $main] = approvedReading();
    $main->update(['product_key' => Product::SNAPSHOT_INTERNAL]);
    app(PublishService::class)->publish($main->refresh(), $setup['admin']);
    actingAsCustomer($setup['leader']);
    $parameters = [$setup['organization'], $main->diagnosis];
    $strength = EvidenceTrace::query()->firstOrFail()->diagnosis_row_id;

    $this->get(route('client.report.show', $parameters))->assertOk()
        ->assertSee('Mennyire lesz nálatok az értékekből tett?')
        ->assertSee('Nálatok szólnak a csúszásról.')
        ->assertSee('A recommendation mondata.')
        ->assertSee('rounded-[18px] bg-yellow', false)
        ->assertSee('href="'.route('client.report.item', [...$parameters, 'ertekek', $strength]).'"', false)
        ->assertSee('Magatokra ismertek?');
    $this->get(route('client.report.summary', $parameters))->assertNotFound();
});
