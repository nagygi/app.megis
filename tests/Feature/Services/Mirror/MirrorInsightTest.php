<?php

use App\Ai\AiContext;
use App\Enums\InboxItemType;
use App\Enums\MaterialContext;
use App\Enums\MirrorInsightStatus;
use App\Enums\ValueOrigin;
use App\Livewire\Admin\Measurements\Index as MeasurementsIndex;
use App\Models\AiCall;
use App\Models\InboxItem;
use App\Models\MirrorSession;
use App\Models\Organization;
use App\Models\Value;
use App\Services\Ai\AiGateway;
use App\Services\Materials\MaterialStore;
use App\Services\Mirror\MirrorInsightService;
use App\Services\Mirror\MirrorService;
use App\Services\Values\PersonalValueSetService;
use App\Services\Values\ValueService;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\StructuredTextResponse;
use Livewire\Livewire;

function insightAnswer(array $data): StructuredTextResponse
{
    return new StructuredTextResponse($data, json_encode($data), new TextUsage(100, 50), new Meta('anthropic', 'claude-test-1'));
}

/**
 * The Coach's answer for the two values of deepenedMirror(): V1 matched, V2 free.
 */
function coachAnswer(): StructuredTextResponse
{
    return insightAnswer(['values' => [
        ['ref' => 'V1', 'meaning' => 'Kimondom, amíg még számít.', 'signs' => [' szólok, ha csúszik ', 'nem szépítem', '', 'felfelé is', 'mindig', 'ötödik'], 'low' => 'Bólintok, aztán hallgatok.', 'high' => 'Ott, akkor, annak.'],
        ['ref' => 'V2', 'meaning' => 'A nehézben is nevetek.', 'signs' => ['humor'], 'low' => 'Elfelejtek nevetni.', 'high' => 'Nevetünk a végén.'],
        ['ref' => 'V9', 'meaning' => 'Kitalált.', 'signs' => [], 'low' => '', 'high' => ''],
    ]]);
}

/**
 * A mirror with two values, the first matched to the Értéktár, the second free;
 * with an organization it is the own menu's.
 *
 * @param  list<StructuredTextResponse>  $more  answers after the three engine steps
 */
function deepenedMirror(?Organization $organization = null, array $more = []): MirrorSession
{
    app(ValueService::class)->create(['name' => 'Egyenesség', 'definition' => 'Kimondjuk, ami fontos.', 'meanings' => ['Kimondom.'], 'high_examples' => ['Mindig.'], 'low_examples' => ['Soha.']]);
    AiGateway::fake([
        insightAnswer(['detections' => [
            ['speaker' => 'Beszélő 1', 'quote' => 'inkább most mondom, mint csütörtökön', 'free_name' => 'egyenes beszéd', 'reasoning' => 'Rögtön kimondja.', 'strength' => 5],
            ['speaker' => 'Beszélő 1', 'quote' => 'a végén nevettünk egyet', 'free_name' => 'Játékosság', 'reasoning' => 'A nehézben is marad humor.', 'strength' => 3],
        ]]),
        insightAnswer(['results' => [
            ['detection' => 'D1', 'matches' => [['ref' => 'E1', 'confidence' => 0.9]]],
            ['detection' => 'D2', 'matches' => []],
        ]]),
        insightAnswer(['scores' => [['ref' => 'E1', 'state' => 'scored', 'score' => 5, 'reasoning' => 'Így tesz.', 'detections' => ['D1']]]]),
        ...$more,
    ]);

    $user = $organization?->users()->first();
    $material = app(MaterialStore::class)->fromText(
        'Múlt héten inkább most mondom, mint csütörtökön. A végén nevettünk egyet.',
        $organization ? MaterialContext::Mirror : MaterialContext::PublicMirror,
        $user,
        $organization,
    );

    return app(MirrorService::class)->start($material, $user, $organization);
}

it('puts every value into the user’s words in one call after the measurement', function () {
    config(['megis.mirror.deepening' => true]);
    $session = deepenedMirror(more: [coachAnswer()]);
    $directness = Value::query()->firstWhere('name', 'Egyenesség');

    $call = AiCall::query()->where('task', 'mirror_deepening')->sole();
    $insights = $session->insights()->get()->keyBy('group_key');

    expect($session->fresh()->insight_status)->toBe(MirrorInsightStatus::Ready)
        ->and($call->prompt)->toBe('ertektukor-elmelyules')
        ->and($call->prompt_version)->toBe(1)
        ->and($call->public_mirror)->toBeTrue()
        ->and($insights->keys()->sort()->values()->all())->toBe(['free:játékosság', 'value:'.$directness->id])
        ->and($insights['value:'.$directness->id])
        ->meaning->toBe('Kimondom, amíg még számít.')
        ->signs->toBe(['szólok, ha csúszik', 'nem szépítem', 'felfelé is', 'mindig'])
        ->low_example->toBe('Bólintok, aztán hallgatok.')
        ->high_example->toBe('Ott, akkor, annak.')
        ->ai_call_id->toBe($call->id)
        ->accepted_at->toBeNull();
});

it('does not call the Coach when ET4 is switched off', function () {
    $session = deepenedMirror();

    expect($session->fresh()->insight_status)->toBeNull()
        ->and(AiCall::query()->where('task', 'mirror_deepening')->exists())->toBeFalse();
});

it('marks the suggestions failed and opens a Beérkező row that starts them again', function () {
    $session = deepenedMirror();
    config(['megis.mirror.deepening' => true]);
    AiGateway::fake(fn () => throw new RuntimeException('Provider down'));

    try {
        app(MirrorInsightService::class)->start($session);
    } catch (RuntimeException) {
        // The sync queue rethrows after marking the job failed.
    }

    $item = InboxItem::query()->sole();
    expect($session->fresh()->insight_status)->toBe(MirrorInsightStatus::Failed)
        ->and($item->type)->toBe(InboxItemType::ProcessingError)
        ->and($item->is_measurement)->toBeTrue()
        ->and($item->payload['job'])->toBe('mirror_deepening');

    actingAsAdmin();
    AiGateway::fake([coachAnswer()]);

    Livewire::test(MeasurementsIndex::class)->call('retry', $item->id);

    expect($session->fresh()->insight_status)->toBe(MirrorInsightStatus::Ready)
        ->and($session->insights()->count())->toBe(2)
        ->and($item->fresh()->closed_at)->not->toBeNull();
});

it('brings the meaning taken on ET4 into the own values', function () {
    config(['megis.mirror.deepening' => true]);
    $organization = Organization::factory()->create();
    $user = customerOf($organization);
    $session = deepenedMirror($organization, [coachAnswer()]);
    $directness = Value::query()->firstWhere('name', 'Egyenesség');

    $group = app(MirrorService::class)->groups($session)->firstWhere('key', 'value:'.$directness->id);
    app(MirrorInsightService::class)->edit($session, $group, ['name' => 'Egyenesség', 'meaning' => 'Akkor szólok, amikor még számít.', 'signs' => [], 'low' => 'Bólintok, aztán hallgatok.', 'high' => 'Ott, akkor, annak.']);

    $item = collect(app(PersonalValueSetService::class)->itemsFromMirror($session->fresh(), [], 5))->firstWhere('value_id', $directness->id);

    expect($session->fresh()->user_id)->toBe($user->id)
        ->and($item)
        ->meaning->toBe('Akkor szólok, amikor még számít.')
        ->low_example->toBe('Bólintok, aztán hallgatok.')
        ->high_example->toBe('Ott, akkor, annak.')
        ->example->toBe('inkább most mondom, mint csütörtökön');
});

it('brings a value renamed on ET4 into the own values as the user’s own, the Értéktár unchanged', function () {
    config(['megis.mirror.deepening' => true]);
    $organization = Organization::factory()->create();
    $user = customerOf($organization);
    $session = deepenedMirror($organization, [coachAnswer()]);
    $directness = Value::query()->firstWhere('name', 'Egyenesség');
    $group = app(MirrorService::class)->groups($session)->firstWhere('key', 'value:'.$directness->id);

    app(MirrorInsightService::class)->edit($session, $group, ['name' => 'Kiállás', 'meaning' => 'Kiállok, ha kell.', 'signs' => ['szólok'], 'low' => 'Hallgatok.', 'high' => 'Kimondom.']);

    $sets = app(PersonalValueSetService::class);
    $set = $sets->forUser($user);
    $sets->save($set, $sets->itemsFromMirror($session->fresh(), [], 5), recordVersion: false);
    $item = $set->items()->with('value')->get()->firstWhere('name', 'Kiállás');

    expect($item)->not->toBeNull()
        ->and($item->meaning)->toBe('Kiállok, ha kell.')
        ->and($item->value->origin)->toBe(ValueOrigin::User)
        ->and($item->value->origin_user_id)->toBe($user->id)
        ->and($directness->fresh()->name)->toBe('Egyenesség')
        ->and($directness->fresh()->meanings)->toBe(['Kimondom.']);
});

it('keeps what the user wrote or approved when the Coach runs again', function () {
    config(['megis.mirror.deepening' => true]);
    $session = deepenedMirror(more: [coachAnswer()]);
    $directness = Value::query()->firstWhere('name', 'Egyenesség');
    $mirrors = app(MirrorService::class);
    $insights = app(MirrorInsightService::class);
    $free = $mirrors->groups($session)->firstWhere('key', 'free:játékosság');

    $insights->edit($session, $mirrors->groups($session)->firstWhere('key', 'value:'.$directness->id), ['name' => 'Kiállás', 'meaning' => 'Az én szavaim.', 'signs' => [], 'low' => null, 'high' => null]);
    $insights->approve($session, $free->key);

    AiGateway::fake([insightAnswer(['values' => [
        ['ref' => 'V1', 'meaning' => 'Új javaslat.', 'signs' => [], 'low' => '', 'high' => ''],
        ['ref' => 'V2', 'meaning' => 'Másik új javaslat.', 'signs' => [], 'low' => '', 'high' => ''],
    ]])]);
    $insights->deepen($session, AiContext::publicMirror($session));

    $byKey = $insights->forSession($session);

    expect($byKey->get('value:'.$directness->id)->meaning)->toBe('Az én szavaim.')
        ->and($byKey->get('value:'.$directness->id)->name)->toBe('Kiállás')
        ->and($byKey->get($free->key)->meaning)->toBe('A nehézben is nevetek.')
        ->and($byKey->get($free->key)->accepted_at)->not->toBeNull();
});

it('approves a value the Coach had no words for', function () {
    $session = deepenedMirror();
    $insights = app(MirrorInsightService::class);
    $group = app(MirrorService::class)->groups($session)->first();

    $insights->approve($session, $group->key);

    expect(app(MirrorService::class)->groups($session)->first()->approved)->toBeTrue()
        ->and($insights->forSession($session)->get($group->key)->meaning)->toBeNull();
});
