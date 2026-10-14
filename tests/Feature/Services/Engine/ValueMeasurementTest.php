<?php

use App\Enums\DiagnosisCreator;
use App\Enums\DiagnosisLevel;
use App\Enums\DiagnosisStatus;
use App\Enums\InboxItemType;
use App\Enums\MaterialContext;
use App\Enums\MaterialStatus;
use App\Enums\MirrorStatus;
use App\Enums\ScoreResultState;
use App\Enums\ValueOrigin;
use App\Models\AiCall;
use App\Models\AnonymousValueStat;
use App\Models\InboxItem;
use App\Models\Material;
use App\Models\MirrorSession;
use App\Models\Organization;
use App\Models\Value;
use App\Services\Ai\AiGateway;
use App\Services\Materials\MaterialStore;
use App\Services\Mirror\MirrorService;
use App\Services\Values\ValueService;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\StructuredTextResponse;

function aiAnswer(array $data): StructuredTextResponse
{
    return new StructuredTextResponse($data, json_encode($data), new TextUsage(100, 50), new Meta('anthropic', 'claude-test-1'));
}

/**
 * Two reviewed Értéktár values (E1, E2) and the three engine answers: two
 * detections, the first matched to E1, the second matching nothing.
 *
 * @return array{0: Value, 1: Value}
 */
function engineScenario(int $score = 5): array
{
    $values = app(ValueService::class);
    $directness = $values->create(['name' => 'Egyenesség', 'meanings' => ['Kimondom.'], 'high_examples' => ['Azonnal szólok.'], 'low_examples' => ['Hallgatok.']]);
    $care = $values->create(['name' => 'Gondoskodás', 'meanings' => ['Figyelek.'], 'high_examples' => ['Mindig.'], 'low_examples' => ['Soha.']]);

    AiGateway::fake([
        aiAnswer(['detections' => [
            ['speaker' => 'Beszélő 1', 'quote' => 'inkább most mondom, mint csütörtökön', 'free_name' => 'egyenes beszéd', 'reasoning' => 'Rögtön kimondja.', 'strength' => 5],
            ['speaker' => 'Beszélő 1', 'quote' => 'a végén nevettünk egyet', 'free_name' => 'Játékosság', 'reasoning' => 'A nehézben is marad humor.', 'strength' => 3],
        ]]),
        aiAnswer(['results' => [
            ['detection' => 'D1', 'matches' => [['ref' => 'E1', 'confidence' => 0.9], ['ref' => 'E9', 'confidence' => 0.8]]],
            ['detection' => 'D2', 'matches' => [['ref' => 'E2', 'confidence' => 0.3]]],
        ]]),
        aiAnswer(['scores' => [
            ['ref' => 'E1', 'state' => 'scored', 'score' => $score, 'reasoning' => 'Visszatérően így tesz.', 'detections' => ['D1']],
        ]]),
    ]);

    return [$directness, $care];
}

function textMirror(?Organization $organization = null): MirrorSession
{
    $material = app(MaterialStore::class)->fromText(
        'Múlt héten inkább most mondom, mint csütörtökön. A végén nevettünk egyet.',
        $organization ? MaterialContext::Mirror : MaterialContext::PublicMirror,
        $organization?->users()->first(),
        $organization,
    );

    return app(MirrorService::class)->start($material, $organization?->users()->first(), $organization);
}

it('reads a mirror in three steps and keeps every result with its AI call', function () {
    [$directness] = engineScenario();

    $session = textMirror();
    $material = $session->material->fresh();

    expect($session->fresh()->status)->toBe(MirrorStatus::Ready)
        ->and($material->status)->toBe(MaterialStatus::Processed)
        ->and($material->detections()->count())->toBe(2)
        ->and($material->detections()->first()->aiCall->prompt_version)->toBe(1)
        ->and($material->detections()->first()->matches()->pluck('value_id')->all())->toBe([$directness->id])
        ->and($material->scores()->sole())
        ->value_id->toBe($directness->id)
        ->state->toBe(ScoreResultState::Scored)
        ->score->toBe(5.0)
        ->and(AiCall::query()->pluck('task')->all())->toBe(['mirror_detection', 'mirror_matching', 'mirror_scoring'])
        ->and(AiCall::query()->first()->public_mirror)->toBeTrue();
});

it('shows matched values by their Értéktár name and the rest by the free name', function () {
    engineScenario();
    $session = textMirror();

    $groups = app(MirrorService::class)->groups($session->fresh());

    expect($groups->pluck('name')->all())->toBe(['Egyenesség', 'Játékosság'])
        ->and($groups->first()->quotes)->toBe(['inkább most mondom, mint csütörtökön']);
});

it('writes nothing to the Értéktár before the account is confirmed', function () {
    engineScenario();

    textMirror();

    expect(Value::query()->where('origin', ValueOrigin::Ai)->count())->toBe(0);
});

it('makes a pending user diagnosis from a claimed mirror, without the values marked not mine', function () {
    engineScenario();
    $session = textMirror();
    $mirrors = app(MirrorService::class);
    $user = customerOf();
    $mirrors->toggle($session->fresh(), 'free:játékosság');

    $diagnosis = $mirrors->claim($session->fresh(), $user, $user->organizations()->first());

    expect($diagnosis)
        ->level->toBe(DiagnosisLevel::User)
        ->creator_kind->toBe(DiagnosisCreator::Automatic)
        ->status->toBe(DiagnosisStatus::PendingApproval)
        ->name->toStartWith('Értéktükör · ')
        ->and($diagnosis->type->key)->toBe('mirror')
        ->and($diagnosis->valueRows()->sole())
        ->score->toBe(5.0)
        ->reasoning->toBe('Visszatérően így tesz.')
        ->and($diagnosis->valueRows()->sole()->quotes[0]['quote'])->toBe('inkább most mondom, mint csütörtökön')
        // The excluded free value does not become an Értéktár value.
        ->and(Value::query()->where('origin', ValueOrigin::Ai)->count())->toBe(0)
        ->and($session->fresh())->status->toBe(MirrorStatus::Claimed)->expires_at->toBeNull();
});

it('adds a value that matched nothing to the Értéktár as AI-detected', function () {
    engineScenario();
    $session = textMirror();
    $user = customerOf();

    app(MirrorService::class)->claim($session->fresh(), $user, $user->organizations()->first());

    $detected = Value::query()->where('origin', ValueOrigin::Ai)->sole();
    expect($detected)
        ->name->toBe('Játékosság')
        ->meanings->toBe(['A nehézben is marad humor.'])
        ->reviewed_at->toBeNull()
        ->origin_organization_id->toBe($user->organizations()->first()->id)
        ->and($session->material->detections()->where('free_name', 'Játékosság')->sole()->unmatched_value_id)->toBe($detected->id);
});

it('gives a detected name that a regular value already has the (detektált) suffix', function () {
    engineScenario();
    Value::factory()->create(['name' => 'Játékosság']);
    $session = textMirror();
    $user = customerOf();

    app(MirrorService::class)->claim($session->fresh(), $user, $user->organizations()->first());

    expect(Value::query()->where('origin', ValueOrigin::Ai)->sole()->name)->toBe('Játékosság (detektált)');
});

it('adds the new occurrence to an existing AI-detected value', function () {
    engineScenario();
    $existing = Value::factory()->from(ValueOrigin::Ai)->create(['name' => 'Játékosság']);
    $session = textMirror();
    $user = customerOf();

    app(MirrorService::class)->claim($session->fresh(), $user, $user->organizations()->first());

    expect(Value::query()->where('origin', ValueOrigin::Ai)->count())->toBe(1)
        ->and($session->material->detections()->where('free_name', 'Játékosság')->sole()->unmatched_value_id)->toBe($existing->id);
});

it('turns a signed-in mirror into a diagnosis of the active organization at once', function () {
    [$directness] = engineScenario();
    $organization = Organization::factory()->create();
    customerOf($organization);

    $session = textMirror($organization);

    $diagnosis = $session->fresh()->diagnosis;
    expect($diagnosis->organization_id)->toBe($organization->id)
        ->and($diagnosis->valueRows()->count())->toBe(1);

    // "nem az enyém" afterwards takes the row out of the pending diagnosis.
    app(MirrorService::class)->toggle($session->fresh(), 'value:'.$directness->id);

    expect($diagnosis->valueRows()->count())->toBe(0);
});

it('shows the error state and opens a Beérkező row when processing fails for good', function () {
    Value::factory()->create();
    AiGateway::fake(fn () => throw new RuntimeException('Provider down'));

    try {
        textMirror();
    } catch (RuntimeException) {
        // The sync queue rethrows after marking the job failed.
    }

    $session = MirrorSession::query()->sole();
    expect($session->status)->toBe(MirrorStatus::Failed)
        ->and($session->material->status)->toBe(MaterialStatus::Failed)
        ->and(InboxItem::query()->sole()->type)->toBe(InboxItemType::ProcessingError);
});

it('reads a long transcript in parts', function () {
    config(['megis.engine.chunk_characters' => 40]);
    Value::factory()->create();
    AiGateway::fake(fn () => aiAnswer(['detections' => []]));

    $material = app(MaterialStore::class)->fromText(str_repeat('Egy mondat a hétről. ', 6), MaterialContext::PublicMirror);
    app(MirrorService::class)->start($material);

    expect(AiCall::query()->where('task', 'mirror_detection')->count())->toBeGreaterThan(1);
});

it('deletes an unclaimed mirror after 24 hours and keeps only the anonymous statistics', function () {
    [$directness] = engineScenario();
    $session = textMirror();
    $materialId = $session->material_id;

    $this->travel(25)->hours();
    $count = app(MirrorService::class)->pruneExpired();

    expect($count)->toBe(1)
        ->and(MirrorSession::query()->count())->toBe(0)
        ->and(Material::query()->find($materialId))->toBeNull()
        ->and(AnonymousValueStat::query()->orderBy('id')->get(['free_name', 'value_id'])->toArray())->toBe([
            ['free_name' => 'Egyenes beszéd', 'value_id' => $directness->id],
            ['free_name' => 'Játékosság', 'value_id' => null],
        ]);
});

it('keeps a claimed mirror', function () {
    engineScenario();
    $session = textMirror();
    $user = customerOf();
    app(MirrorService::class)->claim($session->fresh(), $user, $user->organizations()->first());

    $this->travel(25)->hours();

    expect(app(MirrorService::class)->pruneExpired())->toBe(0);
});
