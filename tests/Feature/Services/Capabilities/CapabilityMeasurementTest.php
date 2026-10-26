<?php

use App\Enums\CapabilityConfidence;
use App\Enums\CapabilityLevel;
use App\Enums\CapabilityMeasurementStatus;
use App\Enums\CapabilityState;
use App\Enums\FindingKind;
use App\Enums\FindingStatus;
use App\Enums\InboxItemType;
use App\Enums\MainDiagnosisStage;
use App\Enums\MaterialContext;
use App\Enums\MaterialStatus;
use App\Enums\PatternOrigin;
use App\Enums\PatternSubtype;
use App\Enums\PreconditionValue;
use App\Enums\TraceDirection;
use App\Enums\TraceDirectness;
use App\Exceptions\DiagnosisException;
use App\Jobs\DetectCapabilities;
use App\Livewire\Admin\Diagnoses\Capabilities;
use App\Livewire\Admin\Inbox\Index as Inbox;
use App\Models\Capability;
use App\Models\CapabilityAssessment;
use App\Models\CapabilityFinding;
use App\Models\CapabilityTrace;
use App\Models\Diagnosis;
use App\Models\InboxItem;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\Organization;
use App\Models\Pattern;
use App\Models\Precondition;
use App\Models\User;
use App\Services\Ai\AiGateway;
use App\Services\Capabilities\CapabilityAssessmentService;
use App\Services\Capabilities\CapabilityCatalog;
use App\Services\Capabilities\CapabilityMeasurementPolicy;
use App\Services\Capabilities\CapabilityReviewService;
use App\Services\Capabilities\PatternCatalog;
use App\Services\Capabilities\PatternStatistics;
use App\Services\Materials\MaterialPipeline;
use App\Services\Materials\MaterialStore;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

/**
 * A small library: RC-46 with a presence pattern seen at one client, an
 * absence pattern that is still a hypothesis, and a detector error.
 *
 * @return array{capability: Capability, presence: Pattern, absence: Pattern, detector: Pattern}
 */
function measurementLibrary(): array
{
    $capability = Capability::factory()->create([
        'code' => 'RC-46',
        'name' => 'Működési ritmus',
        'definition' => 'A csapat ritmusban dolgozik, a DXC-nél is látszott (DXCK-23).',
        'level' => CapabilityLevel::Team,
        'non_examples' => ['Csak naptárbejegyzés, döntés nélkül.'],
    ]);
    $presence = Pattern::factory()->create([
        'code' => 'M-001', 'name' => 'Futó ciklusritmus', 'subtype' => PatternSubtype::Presence,
        'origin' => PatternOrigin::OwnCase, 'sheet_approved_clients' => 1,
        'content' => ['behavior' => 'A heti alkalom hónapok óta fut.', 'conditions' => 'TITKOS-FELTETEL az SZV-nél'],
    ]);
    $absence = Pattern::factory()->create([
        'code' => 'M-002', 'name' => 'Elfogyó alkalom', 'subtype' => PatternSubtype::Absence,
        'origin' => PatternOrigin::External, 'sheet_approved_clients' => 0,
        'content' => ['behavior' => 'Nyomás alatt először az alkalom marad el.'],
    ]);
    $detector = Pattern::factory()->detector()->create(['code' => 'M-187', 'name' => 'Csonkolt idézet', 'content' => ['behavior' => 'Az idézet a gondolat közepén ér véget.']]);
    $presence->capabilities()->attach($capability, ['fit' => 'exact', 'position' => 0]);
    $absence->capabilities()->attach($capability, ['fit' => 'partial', 'position' => 0]);

    return compact('capability', 'presence', 'absence', 'detector');
}

/**
 * A signed-in mirror of a leader with a diagnosis built from it (06 · 6.2).
 *
 * @return array{organization: Organization, material: Material, diagnosis: Diagnosis}
 */
function measuredMirror(): array
{
    $organization = Organization::factory()->create();
    $user = User::factory()->create();
    $material = Material::factory()->transcribed([
        ['speaker' => 'Kérdező', 'text' => 'Hogyan döntötök a heti alkalmon?'],
        ['speaker' => 'Beszélő 1', 'text' => 'A heti alkalom fél éve fut, és minden héten döntünk a következő lépésről.'],
        ['speaker' => 'Beszélő 1', 'text' => 'Amikor sok a munka, először a heti alkalom marad el.'],
        ['speaker' => 'Beszélő 1', 'text' => 'A döntést nem mi hozzuk, a központ dönt a büdzséről.'],
    ])->create([
        'organization_id' => $organization->id,
        'user_id' => $user->id,
        'context' => MaterialContext::Mirror,
        'status' => MaterialStatus::Processed,
    ]);
    $diagnosis = Diagnosis::factory()->forUser($user, $organization)->create(['material_id' => $material->id]);

    return compact('organization', 'material', 'diagnosis');
}

/**
 * Step A finds four traces (one of them the interviewer's, one not in the
 * material), step B matches the first two to the patterns and blocks one.
 */
function fakeCapabilityAi(array &$inputs = []): void
{
    AiGateway::fake(function (string $prompt) use (&$inputs) {
        $inputs[] = $prompt;

        if (str_starts_with($prompt, 'Nyomok:')) {
            return ['findings' => [
                ['pattern' => 'P1', 'traces' => ['N1'], 'level' => 'csapat', 'confidence' => 'medium', 'reasoning' => 'A ritmus fél éve fut.'],
                ['pattern' => 'P2', 'traces' => ['N2'], 'level' => 'csapat', 'confidence' => 'low', 'reasoning' => 'Nyomás alatt elmarad.'],
                ['pattern' => 'P9', 'traces' => ['N1'], 'level' => 'csapat', 'confidence' => 'low', 'reasoning' => 'Nincs ilyen minta.'],
            ], 'blocked' => [['trace' => 'N2', 'detector' => 'X1', 'reason' => 'Csonkolt.']]];
        }

        return ['traces' => [
            ['quote' => 'A heti alkalom fél éve fut, és minden héten döntünk a következő lépésről.', 'location' => '2', 'speaker' => 'Beszélő 1', 'speaker_role' => 'leader', 'level' => 'csapat', 'direction' => 'positive', 'directness' => 'observed', 'raw' => true, 'flags' => [], 'capabilities' => ['K1'], 'preconditions' => [], 'alternative_reading' => 'Csak megszokás.', 'candidate' => 'none', 'candidate_note' => ''],
            ['quote' => 'Amikor sok a munka, először a heti alkalom marad el.', 'location' => '3', 'speaker' => 'Beszélő 1', 'speaker_role' => 'leader', 'level' => 'csapat', 'direction' => 'negative', 'directness' => 'self_report', 'raw' => true, 'flags' => ['bogus_flag'], 'capabilities' => ['K1', 'K99'], 'preconditions' => [], 'alternative_reading' => '', 'candidate' => 'none', 'candidate_note' => ''],
            ['quote' => 'A döntést nem mi hozzuk, a központ dönt a büdzséről.', 'location' => '4', 'speaker' => 'Beszélő 1', 'speaker_role' => 'leader', 'level' => 'szervezet', 'direction' => 'negative', 'directness' => 'self_report', 'raw' => true, 'flags' => [], 'capabilities' => [], 'preconditions' => [['code' => 'E1', 'direction' => 'negative'], ['code' => 'X7', 'direction' => 'negative']], 'alternative_reading' => '', 'candidate' => 'missing_capability', 'candidate_note' => 'Mandátum a büdzséről.'],
            ['quote' => 'Hogyan döntötök a heti alkalmon?', 'location' => '1', 'speaker' => 'Kérdező', 'speaker_role' => 'interviewer', 'level' => 'csapat', 'direction' => 'positive', 'directness' => 'observed', 'raw' => true, 'flags' => [], 'capabilities' => ['K1'], 'preconditions' => [], 'alternative_reading' => '', 'candidate' => 'none', 'candidate_note' => ''],
            ['quote' => 'Ezt senki nem mondta így.', 'location' => '9', 'speaker' => 'Beszélő 1', 'speaker_role' => 'leader', 'level' => 'csapat', 'direction' => 'positive', 'directness' => 'observed', 'raw' => true, 'flags' => [], 'capabilities' => ['K1'], 'preconditions' => [], 'alternative_reading' => '', 'candidate' => 'none', 'candidate_note' => ''],
        ]];
    });
}

beforeEach(function () {
    config(['megis.capabilities.measurement.enabled' => true]);
});

it('measures the materials of a diagnosis, not the public mirror or an occasion outside a diagnosis (NM-12)', function () {
    $policy = app(CapabilityMeasurementPolicy::class);
    $organization = Organization::factory()->create();
    $material = fn (array $attributes) => Material::factory()->create(['organization_id' => $organization->id, ...$attributes]);
    $main = MainDiagnosis::factory()->create();

    expect($policy->appliesTo($material(['context' => MaterialContext::Mirror])))->toBeTrue()
        ->and($policy->appliesTo($material(['context' => MaterialContext::AdminUpload])))->toBeTrue()
        ->and($policy->appliesTo($material(['context' => MaterialContext::Occasion, 'parent_diagnosis_id' => $main->diagnosis_id])))->toBeTrue()
        ->and($policy->appliesTo($material(['context' => MaterialContext::Occasion])))->toBeFalse()
        ->and($policy->appliesTo(Material::factory()->create(['context' => MaterialContext::PublicMirror])))->toBeFalse();

    config(['diagnosis.products.full.capability_measurement' => false]);
    expect($policy->appliesTo($material(['context' => MaterialContext::Occasion, 'parent_diagnosis_id' => $main->diagnosis_id])))->toBeFalse();

    config(['megis.capabilities.measurement.enabled' => false]);
    expect($policy->appliesTo($material(['context' => MaterialContext::Mirror])))->toBeFalse();
});

it('keeps codes, names and case labels out of the catalogs the AI reads (NM-12)', function () {
    measurementLibrary();
    $catalog = CapabilityCatalog::build()->forPrompt();
    $patterns = PatternCatalog::for(Capability::query()->firstWhere('code', 'RC-46'));
    $text = $catalog."\n".$patterns->patternsForPrompt()."\n".$patterns->detectorsForPrompt()."\n".$patterns->capabilityCard();

    expect($text)->toContain('K1 · A csapat ritmusban dolgozik')
        ->toContain('P1 · jelenlét-jel · Futó ciklusritmus')
        ->toContain('X1 · Csonkolt idézet')
        ->toContain('E1 · A fájdalom és a döntési jog egy helyen')
        ->not->toContain('RC-46')
        ->not->toContain('Működési ritmus')
        ->not->toContain('M-001')
        ->not->toContain('DXC')
        ->not->toContain('TITKOS-FELTETEL');
});

it('finds traces and findings, drops the interviewer and a quote not in the material (NM-12)', function () {
    ['capability' => $capability, 'presence' => $presence, 'absence' => $absence] = measurementLibrary();
    ['material' => $material, 'diagnosis' => $diagnosis] = measuredMirror();
    $inputs = [];
    fakeCapabilityAi($inputs);

    DetectCapabilities::dispatchSync($material);

    $traces = $material->capabilityTraces()->orderBy('id')->get();
    $findings = CapabilityFinding::query()->whereBelongsTo($material)->with('traces')->get()->keyBy('pattern_id');

    expect($material->fresh()->capability_status)->toBe(CapabilityMeasurementStatus::Processed)
        ->and($traces)->toHaveCount(3)
        ->and($traces->pluck('quote')->all())->not->toContain('Hogyan döntötök a heti alkalmon?', 'Ezt senki nem mondta így.')
        ->and($traces[0]->subject_level)->toBe(CapabilityLevel::Team)
        ->and($traces[0]->suggested_capability_ids)->toBe([$capability->id])
        ->and($traces[1]->flags)->toBe([])
        ->and($traces[1]->detector_blocks[0]['reason'])->toBe('Csonkolt.')
        ->and($traces[2]->preconditions)->toBe([['code' => 'E1', 'direction' => 'negative']])
        ->and($findings)->toHaveCount(2)
        ->and($findings[$presence->id]->status)->toBe(FindingStatus::Proposed)
        ->and($findings[$presence->id]->machine_confidence)->toBe(CapabilityConfidence::Medium)
        // Seen at one client: no first approval. The literature hypothesis needs one (NY-26).
        ->and($findings[$presence->id]->needs_first_approval)->toBeFalse()
        ->and($findings[$absence->id]->needs_first_approval)->toBeTrue()
        ->and($findings[$presence->id]->traces->pluck('id')->all())->toBe([$traces[0]->id])
        ->and($inputs[0])->toStartWith('Anyag:')
        ->and($inputs[0])->toContain('2. Beszélő 1: A heti alkalom fél éve fut');

    // The diagnosis: present at team level, low (one source); E1 absent; a Beérkező row each for review and candidates.
    $assessment = $diagnosis->capabilityAssessments()->sole();

    expect($assessment->state)->toBe(CapabilityState::Present)
        ->and($assessment->confidence)->toBe(CapabilityConfidence::Low)
        // The absence finding rests on a trace a detector-error pattern blocks: it does not count, not even as pending.
        ->and(collect($assessment->automatic_basis['basis'])->pluck('key')->all())->toBe(['present', 'one_source'])
        ->and($diagnosis->preconditionAssessments()->whereHas('precondition', fn ($query) => $query->where('code', 'E1'))->sole()->value)->toBe(PreconditionValue::Absent)
        ->and($diagnosis->preconditionAssessments()->count())->toBe(9)
        ->and(InboxItem::query()->open()->where('type', InboxItemType::CapabilityReview)->sole()->diagnosis_id)->toBe($diagnosis->id)
        ->and(InboxItem::query()->open()->where('type', InboxItemType::CapabilityCandidate)->count())->toBe(1);
});

it('keeps what an admin decided when the measurement runs again (NM-12)', function () {
    ['presence' => $presence] = measurementLibrary();
    ['material' => $material, 'diagnosis' => $diagnosis] = measuredMirror();
    fakeCapabilityAi();
    DetectCapabilities::dispatchSync($material);

    $admin = actingAsAdmin();
    $finding = CapabilityFinding::query()->where('pattern_id', $presence->id)->sole();
    app(CapabilityReviewService::class)->confirmFinding($diagnosis, $finding, $admin);
    $excluded = $material->capabilityTraces()->where('location', '4')->sole();
    app(CapabilityReviewService::class)->excludeTrace($diagnosis, $excluded, $admin);

    DetectCapabilities::dispatchSync($material, force: true);

    expect(CapabilityFinding::query()->where('pattern_id', $presence->id)->where('status', FindingStatus::Confirmed)->count())->toBe(1)
        ->and(CapabilityFinding::query()->where('pattern_id', $presence->id)->count())->toBe(1)
        ->and($excluded->fresh()->excluded_at)->not->toBeNull()
        ->and($material->capabilityTraces()->count())->toBe(3)
        ->and($presence->fresh()->approved_organizations)->toBe(1)
        ->and($presence->fresh()->maturity()->value)->toBe('used')
        // The excluded E1 trace no longer counts.
        ->and($diagnosis->preconditionAssessments()->whereHas('precondition', fn ($query) => $query->where('code', 'E1'))->sole()->value)->toBe(PreconditionValue::Unknown);
});

it('opens its own Beérkező row when it fails, and "Újra" restarts only the capability measurement (NM-12)', function () {
    measurementLibrary();
    ['material' => $material] = measuredMirror();
    AiGateway::fake(fn () => throw new RuntimeException('Provider timeout'));

    $job = new DetectCapabilities($material);

    try {
        app()->call([$job, 'handle']);
    } catch (RuntimeException) {
        $job->failed(new RuntimeException('Provider timeout'));
    }

    $item = InboxItem::query()->where('type', InboxItemType::ProcessingError)->sole();

    expect($material->fresh()->capability_status)->toBe(CapabilityMeasurementStatus::Failed)
        ->and($material->fresh()->status)->toBe(MaterialStatus::Processed)
        ->and($item->dedupe_key)->toBe('processing-error:material:'.$material->id.':capability_detection')
        ->and($item->payload)->toBe(['job' => 'capability_detection']);

    actingAsAdmin();
    Queue::fake();

    Livewire::test(Inbox::class)->call('retry', $item->id);

    Queue::assertPushed(DetectCapabilities::class, fn (DetectCapabilities $job) => $job->material->is($material) && $job->force);
    expect($material->fresh()->status)->toBe(MaterialStatus::Processed);
});

it('flags a pattern for review after many false alarms (NY-27)', function () {
    ['absence' => $absence] = measurementLibrary();
    ['material' => $material] = measuredMirror();
    $statistics = app(PatternStatistics::class);

    foreach ([FindingStatus::Rejected, FindingStatus::Rejected, FindingStatus::Confirmed, FindingStatus::Rejected] as $outcome) {
        $finding = CapabilityFinding::query()->create([
            'organization_id' => $material->organization_id, 'material_id' => $material->id, 'capability_id' => Capability::query()->value('id'),
            'pattern_id' => $absence->id, 'kind' => FindingKind::Pattern, 'subject_level' => CapabilityLevel::Team,
            'status' => FindingStatus::Proposed, 'machine_confidence' => CapabilityConfidence::Low,
        ]);
        $statistics->record($finding, $outcome);
    }

    expect($absence->fresh()->false_alarms)->toBe(3)
        ->and($absence->fresh()->suggestions)->toBe(4)
        ->and($absence->fresh()->review_flagged_at)->not->toBeNull()
        ->and(InboxItem::query()->open()->where('type', InboxItemType::PatternReview)->sole()->subject->is($absence))->toBeTrue();
});

it('lets the admin decide on the capability page, "magas" only by hand (NM-12)', function () {
    ['presence' => $presence, 'absence' => $absence] = measurementLibrary();
    ['material' => $material, 'diagnosis' => $diagnosis] = measuredMirror();
    fakeCapabilityAi();
    DetectCapabilities::dispatchSync($material);
    actingAsAdmin();
    $assessment = $diagnosis->capabilityAssessments()->sole();
    $e1 = $diagnosis->preconditionAssessments()->whereHas('precondition', fn ($query) => $query->where('code', 'E1'))->sole();

    Livewire::test(Capabilities::class, ['diagnosis' => $diagnosis])
        ->assertSee('RC-46')
        ->assertSee('A heti alkalom fél éve fut')
        ->assertSee('egyenként hagyd jóvá (NY-26)')
        ->assertSee('Kevés még az adat')
        ->call('rejectFinding', CapabilityFinding::query()->where('pattern_id', $absence->id)->value('id'))
        ->call('confirmCapability', $presence->capabilities()->value('capabilities.id'), 'team')
        ->call('startOverride', $assessment->id)
        ->set('overrideConfidence', 'high')
        ->set('overrideReason', '')
        ->call('saveOverride')
        ->assertHasErrors(['overrideReason'])
        ->set('overrideReason', 'Három alkalom, két módszer, megfigyelt viselkedés.')
        ->call('saveOverride')
        ->assertHasNoErrors()
        ->set('preconditionValues.'.$e1->id, 'partial')
        ->set('preconditionNotes.'.$e1->id, 'A büdzsé a központé, a ciklus a miénk.')
        ->call('savePrecondition', $e1->id);

    expect(CapabilityFinding::query()->where('pattern_id', $absence->id)->value('status'))->toBe(FindingStatus::Rejected)
        ->and(CapabilityFinding::query()->where('pattern_id', $presence->id)->value('status'))->toBe(FindingStatus::Confirmed)
        ->and($assessment->fresh()->confidence)->toBe(CapabilityConfidence::High)
        ->and($assessment->fresh()->automatic_confidence)->not->toBe(CapabilityConfidence::High)
        ->and($assessment->fresh()->isApproved())->toBeTrue()
        ->and($e1->fresh()->value)->toBe(PreconditionValue::Partial)
        ->and($e1->fresh()->isOverridden())->toBeTrue()
        ->and($absence->fresh()->false_alarms)->toBe(1)
        // No proposal left: the review row closes.
        ->and(InboxItem::query()->open()->where('type', InboxItemType::CapabilityReview)->count())->toBe(0);
});

it('keeps the capabilities of a published main diagnosis as they are (NM-12)', function () {
    $main = MainDiagnosis::factory()->create(['stage' => MainDiagnosisStage::Published]);
    $assessment = CapabilityAssessment::query()->create([
        'diagnosis_id' => $main->diagnosis_id,
        'capability_id' => Capability::factory()->create()->id,
        'subject_level' => CapabilityLevel::Team,
        'automatic_state' => CapabilityState::Present,
        'state' => CapabilityState::Present,
    ]);

    expect(fn () => app(CapabilityReviewService::class)->approveAssessment($main->diagnosis, $assessment, User::factory()->admin()->create()))
        ->toThrow(DiagnosisException::class);
});

it('opens the capability page for admins only (NM-12)', function () {
    ['diagnosis' => $diagnosis] = measuredMirror();

    $this->get(route('admin.diagnoses.capabilities', $diagnosis))->assertRedirect(route('admin.login'));

    actingAsAdmin();
    $this->get(route('admin.diagnoses.capabilities', $diagnosis))->assertOk()->assertSee('Még nincs képesség-találat');
    $this->get(route('admin.diagnoses.show', $diagnosis))->assertOk()->assertSee(route('admin.diagnoses.capabilities', $diagnosis));
});

it('never measures while the switch is off, so the value tests stay as they are (NM-12)', function () {
    config(['megis.capabilities.measurement.enabled' => false]);
    measurementLibrary();
    ['material' => $material, 'diagnosis' => $diagnosis] = measuredMirror();
    AiGateway::fake([]);

    DetectCapabilities::dispatchSync($material);

    expect($material->fresh()->capability_status)->toBe(CapabilityMeasurementStatus::Skipped)
        ->and(CapabilityTrace::query()->count())->toBe(0)
        ->and($diagnosis->preconditionAssessments()->count())->toBe(0)
        ->and(Precondition::query()->count())->toBe(9);
});

/**
 * A situation of the Képességek step with a confirmed presence finding on an observed, raw trace.
 */
function presenceSituation(MainDiagnosis $main, User $user, array $library, string $quote): Material
{
    $material = app(MaterialStore::class)->fromTurns([
        ['speaker' => 'mégis (kérdés)', 'text' => 'Mikor került valamibe nemrég ez az érték?'],
        ['speaker' => 'Vezető', 'text' => $quote],
    ], MaterialContext::CapabilityStory, $user, $main->diagnosis->organization);
    $material->update(['parent_diagnosis_id' => $main->diagnosis_id, 'status' => MaterialStatus::Processed]);
    $trace = CapabilityTrace::query()->create([
        'organization_id' => $material->organization_id, 'material_id' => $material->id, 'speaker' => 'Vezető', 'speaker_role' => 'leader',
        'quote' => $quote, 'subject_level' => CapabilityLevel::Team, 'direction' => TraceDirection::Positive,
        'directness' => TraceDirectness::Observed, 'is_raw' => true,
    ]);
    CapabilityFinding::query()->create([
        'organization_id' => $material->organization_id, 'material_id' => $material->id, 'capability_id' => $library['capability']->id,
        'pattern_id' => $library['presence']->id, 'kind' => FindingKind::Pattern, 'subject_level' => CapabilityLevel::Team,
        'status' => FindingStatus::Confirmed, 'machine_confidence' => CapabilityConfidence::Medium,
    ])->traces()->attach($trace);

    return $material;
}

it('reads a situation of the Képességek step for capabilities only, as self-report (NM-12)', function () {
    measurementLibrary();
    [$setup, $main] = runningDiagnosis();
    $inputs = [];
    fakeCapabilityAi($inputs);
    $material = app(MaterialStore::class)->fromTurns([
        ['speaker' => 'mégis (kérdés)', 'text' => 'Mikor került valamibe nemrég ez az érték?'],
        ['speaker' => 'Vezető', 'text' => 'A heti alkalom fél éve fut, és minden héten döntünk a következő lépésről.'],
    ], MaterialContext::CapabilityStory, $setup['leader'], $setup['organization']);
    $material->update(['parent_diagnosis_id' => $main->diagnosis_id]);

    app(MaterialPipeline::class)->start($material);

    // No value measurement: only the two capability steps asked the AI.
    expect($material->fresh()->status)->toBe(MaterialStatus::Processed)
        ->and($material->fresh()->capability_status)->toBe(CapabilityMeasurementStatus::Processed)
        ->and($material->scores()->count())->toBe(0)
        ->and(collect($inputs)->every(fn (string $prompt) => str_starts_with($prompt, 'Anyag:') || str_starts_with($prompt, 'Nyomok:')))->toBeTrue()
        ->and($inputs[0])->toStartWith('Anyag: Képesség-helyzet')
        ->and($inputs[0])->toContain('a kitöltő saját leírása (önbevallás)')
        ->and(CapabilityTrace::query()->where('material_id', $material->id)->pluck('quote')->all())
        ->toBe(['A heti alkalom fél éve fut, és minden héten döntünk a következő lépésről.']);
});

it('counts the situations one person told as one source, another person\'s as another (KO-02)', function () {
    $library = measurementLibrary();
    [$setup, $main] = runningDiagnosis();
    $assessments = app(CapabilityAssessmentService::class);
    $confidence = fn () => CapabilityAssessment::query()->where('diagnosis_id', $main->diagnosis_id)->where('capability_id', $library['capability']->id)->sole()->automatic_confidence;

    presenceSituation($main, $setup['leader'], $library, 'Kedden mindenki elmondta, mi akadt el.');
    presenceSituation($main, $setup['leader'], $library, 'Pénteken a retrón is sorra vettük, mi csúszott.');
    $assessments->refresh($main->diagnosis);

    expect($confidence())->toBe(CapabilityConfidence::Low);

    presenceSituation($main, $setup['peers'][0], $library, 'Szerdán az akadály-körön én is elmondtam a csúszást.');
    $assessments->refresh($main->diagnosis);

    expect($confidence())->toBe(CapabilityConfidence::Medium);
});
