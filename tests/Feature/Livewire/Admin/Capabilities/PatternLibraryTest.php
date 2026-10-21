<?php

use App\Enums\CapabilityLevel;
use App\Enums\CapabilityRuleArea;
use App\Enums\CapabilityRuleStatus;
use App\Enums\LevelMarker;
use App\Enums\PatternFit;
use App\Enums\PatternMaturity;
use App\Enums\PatternProduct;
use App\Enums\PatternSubtype;
use App\Enums\PatternType;
use App\Enums\PatternValidity;
use App\Livewire\Admin\Capabilities\Import;
use App\Livewire\Admin\Patterns\Index;
use App\Models\Capability;
use App\Models\CapabilityImport;
use App\Models\CapabilityRule;
use App\Models\Pattern;
use App\Services\Capabilities\CapabilityImporter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function patternHeader(): array
{
    return [
        'minta-azonosító', 'típus', 'altípus', 'név', 'kapcsolt képesség(ek)', 'minta-érettség', 'illeszkedés', 'előfeltétel',
        'szint', 'jelölés', 'kívülről látható', 'termékek', 'helyzet', 'megfigyelhető viselkedés', 'feltételek', 'mi nem ez',
        'hol látszik', 'eredet', 'érvényes', 'jóváhagyott ügyfelek', 'javasolt találatok', 'téves riasztások', 'bizonyítékok száma',
        'hányszor láttuk (v0.3.2, kézi)',
    ];
}

/**
 * A synthetic export with two capabilities, four patterns (one with a temporary
 * code), the Szabálykönyv, and every case sheet holding a canary that must never come in.
 *
 * @param  array<string, list<string>|null>  $patterns  rows to override by code
 */
function patternExport(array $patterns = []): string
{
    $rows = [
        'M-001' => ['M-001', 'képesség-minta', 'jelenlét-jel', 'Futó ciklusritmus', 'RC-46', 'egy esetben láttuk', 'pontos', 'nem értelmezhető', 'csapat', 'mikro szervezet (SZV)', 'nem', "Teljes\nÉrtékmérés", 'Amikor a csapat ciklusban dolgozik.', 'A heti alkalom hónapok óta fut.', 'Kiegészítés 09.27.: DXC-ből.', '', "naptár\njegyzet", 'saját eset', 'igen', '1', '0', '0', '2', '1 (DXC)'],
        'M-002' => ['M-002', 'képesség-minta', 'anti-minta', 'Rituálé-ritmus', "RC-46\nÚJ-01", 'használt', "RC-46: pontos\nÚJ-01: részleges (szervezet)\nRC-99: pontos", 'E2 (gazda), ha nincs belső vivő', 'szervezet', 'határfelület (vezetői kör)', 'igen (árlista, ajánlat)', "Teljes\nBridge\nGalaxis", '', 'Az alkalomból nem születik döntés.', '', '[JAVASLAT] Nem a ritmus hiánya.', 'naptár', 'mindkettő', 'igen', '2', '1', '0', '3', ''],
        'M-003' => ['M-003', 'detektor-hibaminta', 'nem értelmezhető', 'Csonkolt idézet', '', 'hipotézis (saját megfigyelés)', 'nem értelmezhető', 'nincs E-kód (akadálytípus: vezetői viselkedés)', 'nem értelmezhető', '', 'nem', 'detektor (tiltó szabály)', '', 'Az idézet a gondolat közepén ér véget.', '', '', '', 'saját eset', 'elavult', '0', '0', '0', '0', ''],
        'F33-M01' => ['F33-M01', 'képesség-minta', 'hiány-jel', 'Ideiglenes', 'RC-46', '', 'jelenlét (csapat)', '', 'csapat', '', 'nem', 'Teljes', '', 'Valami.', '', '', '', 'saját eset', 'igen', '0', '0', '0', '0', ''],
    ];

    foreach ($patterns as $code => $row) {
        if ($row === null) {
            unset($rows[$code]);
        } else {
            $rows[$code] = $row;
        }
    }

    $canary = 'ESETADAT-NE-JOJJON-BE';

    return capabilitySpreadsheet([
        'Képesség' => [
            ['azonosító', 'név (magyar)', 'név (angol)', 'elsődleges szint', 'csoport', 'státusz', 'jelleg', 'rövid definíció', 'minták száma', 'megerősített találatok', 'kapcsolt bizonyítékok'],
            ['RC-46', 'Működési ritmus', 'Operating rhythm', 'csapat', 'Operating System & Execution', 'használt', 'képesség', 'A csapat ritmusban dolgozik.', '2', '3', '5'],
            ['ÚJ-01', 'Döntési jog tisztázása', 'Decision rights', 'szervezet', 'Governance, Risk & Accountability', 'jelölt', 'képesség', 'Tisztázzák, ki dönthet miről.', '1', '0', '0'],
        ],
        'Minta' => [patternHeader(), ...array_values($rows)],
        'Találat' => [['azonosító', 'ügyfél', 'minta', 'megjegyzés'], ['T-0001', 'DXC', 'M-001', $canary]],
        'Bizonyíték' => [['azonosító', 'idézet vagy nyom'], ['B-0001', $canary]],
        'Kapcsolat' => [['azonosító', 'bizonyíték', 'minta', 'idézet vagy nyom'], ['K-0001', 'B-0001', 'M-001', $canary]],
        'Esettár' => [['forrás-azonosító', 'fájlnév'], ['DXC-01', $canary]],
        'Szabálykönyv' => [
            ['szabály-azonosító', 'terület (nyom / állapot / konfidencia)', 'szabály szövege', 'státusz (megállapodott / döntés 09.26. / javaslat, elfogadva / javaslat)', 'forrás', 'verzió', 'dátum'],
            ['KO-05', 'konfidencia', 'Csábító tétel: közepeshez legalább két nyers nyom az adott szinten.', 'javaslat, elfogadva', 'v5', 'v0.3.1', '2026.09.26.'],
            ['NY-26', '', 'Hipotézis érettségű minta első találatát ember hagyja jóvá.', 'megállapodott és döntés 09.26.', '', 'v0.4', '2026.10.05.'],
        ],
    ]);
}

function patternUpload(string $path): UploadedFile
{
    return UploadedFile::fake()->createWithContent('kepessegrendszer-tarak.xlsx', file_get_contents($path));
}

it('previews the patterns and rules without writing, then loads them as v1 (NM-19)', function () {
    actingAsAdmin();

    $component = Livewire::test(Import::class)
        ->set('file', patternUpload(patternExport()))
        ->call('showPreview')
        ->assertSee('Minták: 3 új · 0 változott · 0 változatlan')
        ->assertSee('Szabálykönyv: 2 új vagy változott szabály')
        ->assertSee('„F33-M01” nem végleges azonosító', false)
        ->assertSee('M-002: a(z) RC-99 képesség nincs a tárban', false)
        ->assertSee('ismeretlen érték (Termékek): „Galaxis”', false)
        ->assertSee('Minta · hányszor láttuk (v0.3.2, kézi)');

    expect(Pattern::query()->count())->toBe(0)
        ->and(CapabilityRule::query()->count())->toBe(0);

    $component->call('load')->assertHasNoErrors();

    $first = Pattern::query()->firstWhere('code', 'M-001');
    $second = Pattern::query()->firstWhere('code', 'M-002');
    $detector = Pattern::query()->firstWhere('code', 'M-003');

    expect(Pattern::query()->count())->toBe(3)
        ->and($first->type)->toBe(PatternType::Capability)
        ->and($first->subtype)->toBe(PatternSubtype::Presence)
        ->and($first->level)->toBe(CapabilityLevel::Team)
        ->and($first->level_marker)->toBe(LevelMarker::Micro)
        ->and($first->text('marker_note'))->toBe('SZV')
        ->and($first->products)->toBe([PatternProduct::Full->value, PatternProduct::ValueMeasurement->value])
        ->and($first->where_visible)->toBe(['naptár', 'jegyzet'])
        ->and($first->text('behavior'))->toBe('A heti alkalom hónapok óta fut.')
        ->and($first->fieldState('preconditions'))->toBe('not_applicable')
        ->and($first->capabilities->pluck('pivot.fit', 'code')->all())->toBe(['RC-46' => PatternFit::Exact->value])
        ->and($first->maturity())->toBe(PatternMaturity::SeenOnce)
        ->and($first->sheet_evidence_count)->toBe(2)
        ->and($first->versions()->sole()->number)->toBe(1)
        // The multi-line fit applies per capability, with its note; the unknown capability stays out.
        ->and($second->capabilities->mapWithKeys(fn ($capability) => [$capability->code => [$capability->pivot->fit, $capability->pivot->fit_note]])->all())
        ->toBe(['RC-46' => ['exact', null], 'ÚJ-01' => ['partial', 'szervezet']])
        ->and($second->preconditions->pluck('pivot.note', 'code')->all())->toBe(['E2' => 'gazda'])
        ->and($second->precondition_note)->toBe('ha nincs belső vivő')
        ->and($second->level_marker)->toBe(LevelMarker::Boundary)
        ->and($second->externally_visible)->toBeTrue()
        ->and($second->text('externally_visible_note'))->toBe('árlista, ajánlat')
        ->and($second->isProposed('non_examples'))->toBeTrue()
        ->and($second->maturity())->toBe(PatternMaturity::Used)
        ->and($detector->type)->toBe(PatternType::DetectorError)
        ->and($detector->subtype)->toBeNull()
        ->and($detector->validity)->toBe(PatternValidity::Obsolete)
        ->and($detector->precondition_note)->toBe('nincs E-kód (akadálytípus: vezetői viselkedés)')
        ->and($detector->products)->toBe([PatternProduct::Detector->value]);

    $rules = CapabilityRule::query()->get()->keyBy('code');

    expect($rules['KO-05']->area)->toBe(CapabilityRuleArea::Confidence)
        ->and($rules['KO-05']->status)->toBe(CapabilityRuleStatus::Accepted)
        // No area in the cell: the prefix of the code says it.
        ->and($rules['NY-26']->area)->toBe(CapabilityRuleArea::Trace)
        ->and($rules['NY-26']->status)->toBe(CapabilityRuleStatus::Agreed)
        ->and(Capability::query()->firstWhere('code', 'RC-46')->sheet_confirmed_findings)->toBe(3)
        ->and(CapabilityImport::query()->sole()->patterns_created_count)->toBe(3);
});

it('never takes in the case sheets, not even into the pattern texts (NM-19)', function () {
    actingAsAdmin();

    app(CapabilityImporter::class)->import(patternExport(), 'tar.xlsx');

    foreach (['patterns', 'capability_pattern', 'capability_rules', 'capabilities', 'versions'] as $table) {
        $rows = json_encode(DB::table($table)->get());

        expect($rows)->not->toContain('ESETADAT-NE-JOJJON-BE');
    }
});

it('makes a new version only when a pattern text changed, not for the counters (NM-19)', function () {
    actingAsAdmin();
    $importer = app(CapabilityImporter::class);
    $importer->import(patternExport(), 'tar.xlsx');

    $changed = patternExport([
        'M-001' => ['M-001', 'képesség-minta', 'jelenlét-jel', 'Futó ciklusritmus', 'RC-46', 'használt', 'pontos', 'nem értelmezhető', 'csapat', 'mikro szervezet (SZV)', 'nem', "Teljes\nÉrtékmérés", 'Amikor a csapat ciklusban dolgozik.', 'A heti alkalom hónapok óta fut.', 'Kiegészítés 09.27.: DXC-ből.', '', "naptár\njegyzet", 'saját eset', 'igen', '2', '0', '0', '4', '1 (DXC)'],
        'M-002' => ['M-002', 'képesség-minta', 'anti-minta', 'Rituálé-ritmus', "RC-46\nÚJ-01", 'elvetett', "RC-46: pontos\nÚJ-01: részleges (szervezet)", 'E2 (gazda), ha nincs belső vivő', 'szervezet', 'határfelület (vezetői kör)', 'igen (árlista, ajánlat)', "Teljes\nBridge", '', 'Az alkalomból nem születik döntés, csak beszélgetés.', '', '[JAVASLAT] Nem a ritmus hiánya.', 'naptár', 'mindkettő', 'elvetett', '2', '1', '0', '3', ''],
    ]);

    $result = $importer->import($changed, 'tar-2.xlsx');
    $first = Pattern::query()->firstWhere('code', 'M-001');
    $second = Pattern::query()->firstWhere('code', 'M-002');

    expect($result->patternsUnchanged)->toContain('M-001', 'M-003')
        ->and($first->versions()->count())->toBe(1)
        ->and($first->sheet_approved_clients)->toBe(2)
        ->and($first->maturity())->toBe(PatternMaturity::Used)
        ->and($result->patternsUpdated)->toHaveCount(1)
        ->and($result->patternsUpdated[0]['fields'])->toContain('behavior', 'validity')
        ->and($result->patternsInvalidated)->toBe(['M-002'])
        ->and($second->versions()->count())->toBe(2)
        ->and($second->maturity())->toBe(PatternMaturity::Rejected);
});

it('lists the valid patterns by default and filters them (NM-19)', function () {
    actingAsAdmin();
    app(CapabilityImporter::class)->import(patternExport(), 'tar.xlsx');
    $capability = Capability::query()->firstWhere('code', 'ÚJ-01');

    Livewire::test(Index::class)
        ->assertSee('Futó ciklusritmus')
        ->assertSee('Rituálé-ritmus')
        ->assertDontSee('Csonkolt idézet')
        ->set('filters.capability', (string) $capability->id)
        ->assertDontSee('Futó ciklusritmus')
        ->assertSee('Rituálé-ritmus')
        ->set('filters', ['maturity' => PatternMaturity::Obsolete->value])
        ->assertSee('Csonkolt idézet')
        ->set('filters', ['product' => PatternProduct::Bridge->value])
        ->assertSee('Rituálé-ritmus')
        ->assertDontSee('Futó ciklusritmus');
});

it('shows a pattern, its versions and the patterns of a capability (NM-19)', function () {
    actingAsAdmin();
    app(CapabilityImporter::class)->import(patternExport(), 'tar.xlsx');
    $pattern = Pattern::query()->firstWhere('code', 'M-002');
    $capability = Capability::query()->firstWhere('code', 'RC-46');

    $this->get(route('admin.patterns.show', $pattern))->assertOk()
        ->assertSee('Rituálé-ritmus')
        ->assertSee('Az alkalomból nem születik döntés.')
        ->assertSee('E2 · Gazda (gazda)')
        ->assertSee('javaslat');
    $this->get(route('admin.patterns.versions', $pattern))->assertOk()->assertSee('v1');
    $this->get(route('admin.patterns.show', Pattern::query()->firstWhere('code', 'M-003')))->assertOk()
        ->assertSee('ügyfél soha nem látja');
    $this->get(route('admin.capabilities.patterns', $capability))->assertOk()
        ->assertSee('Futó ciklusritmus')
        ->assertSee('anti-minta');
    $this->get(route('admin.capabilities.patterns', Capability::factory()->create()))->assertOk()
        ->assertSee('Nincs minta: a felismerés a definíció alapján értelmezés.');
    $this->get(route('admin.capability-rules.index'))->assertOk()
        ->assertSee('KO-05')
        ->assertSee('Hipotézis érettségű minta első találatát ember hagyja jóvá.');
});

it('keeps the Mintatár and the Szabálykönyv for the admins (NM-19)', function () {
    $pattern = Pattern::factory()->create();

    $this->get(route('admin.patterns.index'))->assertRedirect(route('admin.login'));
    $this->get(route('admin.patterns.show', $pattern))->assertRedirect(route('admin.login'));
    $this->get(route('admin.capability-rules.index'))->assertRedirect(route('admin.login'));

    actingAsAdmin();
    $this->get(route('admin.patterns.index'))->assertOk()->assertSee($pattern->name);
});
