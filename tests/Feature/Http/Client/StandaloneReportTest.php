<?php

use App\Enums\DiagnosisStatus;
use App\Models\Diagnosis;
use App\Models\DiagnosisRow;
use App\Models\DiagnosisText;
use App\Models\MainDiagnosis;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use App\Models\Value;
use App\Services\Diagnoses\DiagnosisService;
use App\Services\Report\StandaloneReportBuilder;
use Spatie\LaravelPdf\Facades\Pdf;

/**
 * A user's Értéktükör diagnosis: one scored value with its quote, and the
 * mégis' recommendation with a subheading and bold (04 · 4.6).
 *
 * @return array{0: Organization, 1: User, 2: Diagnosis, 3: DiagnosisRow}
 */
function standaloneUserDiagnosis(DiagnosisStatus $status = DiagnosisStatus::Published, string $recommendation = "### Hol kezdjük\n\nA **szakmai igényesség** mellett."): array
{
    $organization = Organization::factory()->create();
    $user = customerOf($organization);
    $diagnosis = Diagnosis::factory()->forUser($user, $organization)->ofType('mirror')->create(['name' => 'Értéktükör · 2026.10.26.', 'status' => $status]);
    $value = Value::factory()->create(['name' => 'Kompetens', 'definition' => 'Érti és jól csinálja a dolgát.', 'low_examples' => ['Csak beszél róla.'], 'high_examples' => ['Mindig hozza a minőséget.']]);
    $row = DiagnosisRow::factory()->for($diagnosis)->for($value)->scored(5.0, 3.0)->create([
        'quotes' => [['detection_id' => null, 'speaker' => 'Beszélő 1', 'quote' => 'Meg tudtam volna csinálni, de fél év múlva újra elromlana.']],
    ]);
    DiagnosisText::query()->create(['diagnosis_id' => $diagnosis->id, 'key' => 'recommendation', 'text' => $recommendation, 'status' => 'approved', 'approved_at' => now()]);

    return [$organization, $user, $diagnosis, $row];
}

it('renders the report of a published user diagnosis to that user, page by page (04 · 4.6)', function () {
    [$organization, $user, $diagnosis, $row] = standaloneUserDiagnosis();
    actingAsCustomer($user);
    $parameters = [$organization, $diagnosis];

    $this->get(route('client.report.show', $parameters))->assertOk()
        ->assertSee('A válasz')
        ->assertSee('Mennyire lesz nálad az értékekből tett?')
        ->assertSee('Egy érték')
        ->assertSee('0 / 8 · A válasz')
        ->assertSee(route('client.report.chapter', [...$parameters, 'ertekek']), false);
    $this->get(route('client.report.chapter', [...$parameters, 'ertekek']))->assertOk()
        ->assertSee('Mennyire élnek nálad az értékeid?')
        ->assertSee('Kompetens')
        ->assertSee('3-nak látod, 5-öt mutatsz.')
        ->assertSee('2 / 8 · Értékek')
        ->assertDontSee('Egyén');
    $this->get(route('client.report.item', [...$parameters, 'ertekek', $row->id]))->assertOk()
        ->assertSee('diagnózis-nézet')
        ->assertSee('Mit jelent?')
        ->assertSee('Érti és jól csinálja a dolgát.')
        ->assertSee('Meg tudtam volna csinálni, de fél év múlva újra elromlana.')
        ->assertSee('Kompetens · 1 / 1 érték')
        ->assertSee('A diagnózisod');
    $this->get(route('client.report.closing', $parameters))->assertOk()
        ->assertSee('A diagnózisod')
        ->assertSee('Ez a te diagnózisod.')
        ->assertSee('A képesség, amivel kezdenél')
        ->assertSee('<h3>Hol kezdjük</h3>', false)
        ->assertSee('<strong>szakmai igényesség</strong>', false);
    $this->get(route('client.report.appendix', $parameters))->assertOk()
        ->assertSee('a mégis rögzítette')
        ->assertDontSee('Állítások bizonyítékkal');
});

it('keeps the places of what is coming, and has no summary dot without a main diagnosis', function () {
    [$organization, $user, $diagnosis] = standaloneUserDiagnosis();
    actingAsCustomer($user);

    $this->get(route('client.report.show', [$organization, $diagnosis]))->assertOk()
        ->assertSee('Összefoglaló')
        ->assertDontSee(route('client.report.summary', [$organization, $diagnosis]))
        ->assertSee('Öt érték a megjelenéseken')
        ->assertSee('Képesség')
        ->assertSee('hamarosan');
});

it('has no summary, internal working or comparison without a main diagnosis', function (string $page) {
    [$organization, $user, $diagnosis] = standaloneUserDiagnosis();
    actingAsCustomer($user);

    $this->get(route('client.report.'.$page, [$organization, $diagnosis]))->assertNotFound();
})->with(['summary', 'internal', 'compare']);

it('keeps the report closed until the mégis publishes it', function (DiagnosisStatus $status) {
    [$organization, $user, $diagnosis] = standaloneUserDiagnosis($status);
    actingAsCustomer($user);

    $this->get(route('client.report.show', [$organization, $diagnosis]))->assertForbidden();
})->with([DiagnosisStatus::Draft, DiagnosisStatus::PendingApproval]);

it('shows a user diagnosis to that user only', function () {
    [$organization, , $diagnosis] = standaloneUserDiagnosis();
    $colleague = customerOf($organization);
    Membership::query()->where('user_id', $colleague->id)->update(['is_organization_leader' => true]);
    actingAsCustomer($colleague);

    $this->get(route('client.report.show', [$organization, $diagnosis]))->assertForbidden();
});

it('shows a team diagnosis to its members and the organization\'s leaders', function () {
    $organization = Organization::factory()->create();
    $team = Team::factory()->for($organization)->create();
    $member = customerOf($organization);
    $member->teams()->attach($team);
    $leader = customerOf($organization);
    Membership::query()->where('user_id', $leader->id)->update(['is_organization_leader' => true]);
    $outsider = customerOf($organization);
    $diagnosis = Diagnosis::factory()->forTeam($team)->published()->create();
    DiagnosisRow::factory()->for($diagnosis)->scored(4.0)->create();
    $url = route('client.report.show', [$organization, $diagnosis]);

    actingAsCustomer($member);
    $this->get($url)->assertOk()->assertSee('Mennyire lesz nálatok az értékekből tett?');
    actingAsCustomer($leader);
    $this->get($url)->assertOk();
    actingAsCustomer($outsider);
    $this->get($url)->assertForbidden();
});

it('shows an organization diagnosis to every active member', function () {
    $organization = Organization::factory()->create();
    $diagnosis = Diagnosis::factory()->for($organization)->published()->create();
    DiagnosisRow::factory()->for($diagnosis)->scored(4.0)->create();

    actingAsCustomer(customerOf($organization));

    $this->get(route('client.report.show', [$organization, $diagnosis]))->assertOk();
});

it('refuses a diagnosis of another organization', function () {
    [$organization, $user] = standaloneUserDiagnosis();
    $other = Diagnosis::factory()->published()->create();
    actingAsCustomer($user);

    $this->get(route('client.report.show', [$organization, $other]))->assertNotFound();
});

it('has no report of a subdiagnosis', function () {
    $main = MainDiagnosis::factory()->create();
    $user = customerOf($main->diagnosis->organization);
    $subdiagnosis = Diagnosis::factory()->forUser($user, $main->diagnosis->organization)->published()->create(['parent_id' => $main->diagnosis_id]);
    actingAsCustomer($user);

    $this->get(route('client.report.show', [$main->diagnosis->organization, $subdiagnosis]))->assertNotFound();
});

it('lists the published reports in the menu', function () {
    [$organization, $user, $diagnosis] = standaloneUserDiagnosis();
    Diagnosis::factory()->forUser($user, $organization)->create(['name' => 'Még jóváhagyásra vár', 'status' => DiagnosisStatus::PendingApproval]);
    actingAsCustomer($user);

    $this->get(route('client.home', $organization))->assertOk()
        ->assertSee('Riportok')
        ->assertSee(route('client.report.show', [$organization, $diagnosis]))
        ->assertSee('Értéktükör · 2026.10.26.')
        ->assertDontSee('Még jóváhagyásra vár');
});

it('escapes HTML in the recommendation', function () {
    [$organization, $user, $diagnosis] = standaloneUserDiagnosis(recommendation: "<script>alert(\"x\")</script>\n\n**Bátran**");
    actingAsCustomer($user);

    $this->get(route('client.report.closing', [$organization, $diagnosis]))->assertOk()
        ->assertDontSee('<script>alert("x")</script>', false)
        ->assertSee('&lt;script&gt;', false)
        ->assertSee('<strong>Bátran</strong>', false);
});

it('downloads the report as PDF', function () {
    Pdf::fake();
    [$organization, $user, $diagnosis] = standaloneUserDiagnosis();
    actingAsCustomer($user);

    $this->get(route('client.report.pdf', [$organization, $diagnosis]))->assertOk();

    Pdf::assertRespondedWithPdf(fn ($pdf) => $pdf->viewName === 'pdf.report' && str_starts_with($pdf->downloadName, 'megis-diagnozis-'));
});

it('builds the PDF view of a standalone report', function () {
    [, , $diagnosis] = standaloneUserDiagnosis();

    $html = view('pdf.report', ['report' => app(StandaloneReportBuilder::class)->build($diagnosis), 'title' => $diagnosis->name, 'date' => today()])->render();

    expect($html)->toContain('Ez a te diagnózisod.')
        ->toContain('<strong>szakmai igényesség</strong>')
        ->not->toContain('Mi tartja a diagnózist?');
});

it('leaves out the empty brand base rows of a new diagnosis', function () {
    $organization = Organization::factory()->create();
    $diagnosis = app(DiagnosisService::class)->create([
        'name' => 'Workshop',
        'organization_id' => $organization->id,
        'level' => 'organization',
        'diagnosis_type_id' => Diagnosis::factory()->make()->diagnosis_type_id,
        'diagnosed_on' => today(),
    ]);
    DiagnosisRow::factory()->for($diagnosis)->scored(4.0)->create();

    $report = app(StandaloneReportBuilder::class)->build($diagnosis);

    expect(array_keys($report['chapters']))->toBe(['values'])
        ->and($report['ring']['measuring_points'])->toBe(1);
});
