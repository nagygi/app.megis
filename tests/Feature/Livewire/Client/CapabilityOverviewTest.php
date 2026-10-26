<?php

use App\Enums\CapabilityConfidence;
use App\Enums\CapabilityLevel;
use App\Enums\CapabilityState;
use App\Enums\ConsentKind;
use App\Enums\FindingKind;
use App\Enums\FindingStatus;
use App\Enums\MaterialContext;
use App\Enums\MaterialStatus;
use App\Enums\TraceDirection;
use App\Enums\TraceDirectness;
use App\Models\Capability;
use App\Models\CapabilityAssessment;
use App\Models\CapabilityFinding;
use App\Models\CapabilityTrace;
use App\Models\Consent;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\Product;
use App\Models\User;
use App\Services\Capabilities\CapabilityOverview;
use App\Services\Report\PublishService;
use Illuminate\Support\Facades\Queue;

/**
 * A capability row of the diagnosis, approved unless told otherwise.
 *
 * @param  array<string, mixed>  $attributes
 */
function assessedCapability(MainDiagnosis $main, string $name, CapabilityState $state, CapabilityConfidence $confidence = CapabilityConfidence::Medium, array $attributes = [], array $capability = []): Capability
{
    $model = Capability::factory()->create(['name' => $name, ...$capability]);

    CapabilityAssessment::query()->create([
        'diagnosis_id' => $main->diagnosis_id,
        'capability_id' => $model->id,
        'subject_level' => CapabilityLevel::Team,
        'automatic_state' => $state,
        'automatic_confidence' => $confidence,
        'automatic_basis' => ['basis' => [], 'counts' => ['positive' => $state === CapabilityState::Present ? 3 : 0, 'negative' => $state === CapabilityState::Absent ? 1 : 0]],
        'state' => $state,
        'confidence' => $confidence,
        'approved_at' => now(),
        ...$attributes,
    ]);

    return $model;
}

/**
 * A confirmed finding of the capability on a situation the user told, with one raw quote.
 */
function quotedSituation(MainDiagnosis $main, Capability $capability, User $user, string $quote): void
{
    $material = Material::factory()->create([
        'context' => MaterialContext::CapabilityStory,
        'status' => MaterialStatus::Processed,
        'parent_diagnosis_id' => $main->diagnosis_id,
        'organization_id' => $main->diagnosis->organization_id,
        'user_id' => $user->id,
    ]);
    $trace = CapabilityTrace::query()->create([
        'organization_id' => $material->organization_id, 'material_id' => $material->id, 'speaker' => 'Vezető', 'speaker_role' => 'leader',
        'quote' => $quote, 'subject_level' => CapabilityLevel::Team, 'direction' => TraceDirection::Positive,
        'directness' => TraceDirectness::SelfReport, 'is_raw' => true, 'recorded_on' => today(),
    ]);
    $finding = CapabilityFinding::query()->create([
        'organization_id' => $material->organization_id, 'material_id' => $material->id, 'capability_id' => $capability->id,
        'kind' => FindingKind::Interpretation, 'subject_level' => CapabilityLevel::Team, 'status' => FindingStatus::Confirmed,
        'machine_confidence' => CapabilityConfidence::Low,
    ]);
    $finding->traces()->attach($trace);
}

beforeEach(fn () => Queue::fake());

it('shows the approved capabilities on the home card, the report and the Képességtár, without code or step', function () {
    [$setup, $main] = publishedReport();
    assessedCapability($main, 'Működési ritmus', CapabilityState::Present, capability: ['code' => 'RC-46', 'definition' => 'A csapat ritmusban dolgozik.', 'statement' => 'Mostantól ritmusban dolgozunk, emlékeztetés nélkül.']);
    assessedCapability($main, 'Nyílt ellentmondás', CapabilityState::Absent, CapabilityConfidence::Low);
    assessedCapability($main, 'Jóvá nem hagyott képesség', CapabilityState::Present, attributes: ['approved_at' => null]);
    assessedCapability($main, 'Nincs nyoma képesség', CapabilityState::NoTrace);
    actingAsCustomer($setup['leader']);
    $params = ['organization' => $setup['organization'], 'diagnosis' => $main->diagnosis];

    $this->get(route('client.diagnosis.show', $params))
        ->assertOk()
        ->assertSee('Amire építhettek')
        ->assertSee('Működési ritmus')
        ->assertSee('Ami hiányzik')
        ->assertSee('Nyílt ellentmondás')
        ->assertSee('kevés még az adat')
        ->assertSee('Érettségi szint a második diagnózistól.')
        ->assertSee(route('client.capabilities', $setup['organization']))
        ->assertDontSee('Jóvá nem hagyott képesség')
        ->assertDontSee('Nincs nyoma képesség')
        ->assertDontSee('RC-46');

    $this->get(route('client.report.show', $params))->assertOk()->assertSee('Működési ritmus')->assertSee(route('client.capabilities', $setup['organization']));

    $this->get(route('client.capabilities', $setup['organization']))
        ->assertOk()
        ->assertSee('Melyik képesség van meg, melyik hiányzik?')
        ->assertSee('Jelen lehet · közepes · 3 nyom')
        ->assertSee('Hiányzik · kevés még az adat · 1 nyom')
        ->assertDontSee('Jóvá nem hagyott képesség')
        ->assertDontSee('RC-46');
});

it('limits the card by product and keeps a tempting capability on little data off the missing list', function () {
    [$setup, $main] = publishedReport();

    foreach (range(1, 4) as $index) {
        assessedCapability($main, 'Hiány '.$index, CapabilityState::Absent);
    }

    assessedCapability($main, 'Csábító', CapabilityState::Absent, CapabilityConfidence::Low, capability: ['tempting' => true]);
    $overview = app(CapabilityOverview::class);

    $full = $overview->for($main, $setup['leader']);
    expect($full['gaps'])->toHaveCount(3)
        ->and(collect($full['gaps'])->pluck('name'))->not->toContain('Csábító')
        ->and(collect($full['rows'])->pluck('name'))->toContain('Csábító');

    $main->update(['product_key' => Product::SNAPSHOT_INTERNAL]);
    expect($overview->for($main, $setup['leader'])['gaps'])->toHaveCount(1);
});

it('quotes the viewer\'s own situation, a peer\'s only when quotes are shown and agreed to', function () {
    [$setup, $main] = publishedReport();
    $peer = $setup['peers'][1];
    $capability = assessedCapability($main, 'Működési ritmus', CapabilityState::Present);
    quotedSituation($main, $capability, $peer, 'A társ helyzete a ritmusról.');
    $overview = app(CapabilityOverview::class);

    expect($overview->for($main, $setup['leader'])['rows'][0]['quote'])->toBeNull();

    Consent::create(['user_id' => $peer->id, 'main_diagnosis_id' => $main->id, 'kind' => ConsentKind::Quotes, 'text_version' => 'v', 'text' => 't', 'given_at' => now()]);
    expect($overview->for($main, $setup['leader'])['rows'][0]['quote'])->toMatchArray(['text' => 'A társ helyzete a ritmusról.', 'who' => 'társ']);

    $main->update(['show_peer_quotes' => false]);
    expect($overview->for($main->refresh(), $setup['leader'])['rows'][0]['quote'])->toBeNull();

    quotedSituation($main, $capability, $setup['leader'], 'A saját helyzetem a ritmusról.');
    expect($overview->for($main, $setup['leader'])['rows'][0]['quote'])->toMatchArray(['text' => 'A saját helyzetem a ritmusról.', 'who' => 'te']);
});

it('keeps the Képességtár from those who do not read the report', function () {
    [$setup, $main] = publishedReport();
    assessedCapability($main, 'Működési ritmus', CapabilityState::Present);
    actingAsCustomer($setup['peers'][0]);

    $this->get(route('client.capabilities', $setup['organization']))
        ->assertOk()
        ->assertSee('A diagnózis után itt látod')
        ->assertDontSee('Működési ritmus');
    $this->get(route('client.values', $setup['organization']))->assertOk()->assertDontSee(route('client.capabilities', $setup['organization']));
});

it('waits for the shown capabilities to be approved before publishing', function () {
    [, $main] = approvedReading();
    $publishing = app(PublishService::class);

    expect($publishing->checklist($main))->not->toHaveKey('capabilities');

    assessedCapability($main, 'Nincs nyoma képesség', CapabilityState::NoTrace, attributes: ['approved_at' => null]);
    expect($publishing->checklist($main))->not->toHaveKey('capabilities');

    assessedCapability($main, 'Működési ritmus', CapabilityState::Present, attributes: ['approved_at' => null]);
    expect($publishing->checklist($main)['capabilities'])->toBeFalse()
        ->and($publishing->canPublish($main))->toBeFalse();

    CapabilityAssessment::query()->update(['approved_at' => now()]);
    expect($publishing->checklist($main)['capabilities'])->toBeTrue()
        ->and($publishing->canPublish($main))->toBeTrue();
});
