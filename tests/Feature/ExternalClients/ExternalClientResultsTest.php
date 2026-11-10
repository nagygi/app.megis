<?php

use App\Enums\DiagnosisLevel;
use App\Enums\DiagnosisRowKind;
use App\Enums\ExternalClientItemKind;
use App\Enums\MainDiagnosisStage;
use App\Enums\MaterialContext;
use App\Enums\MaterialStatus;
use App\Enums\ScoreResultState;
use App\Enums\ScoreState;
use App\Enums\ValueOrigin;
use App\Livewire\Admin\MainDiagnoses\ExternalClients;
use App\Livewire\Admin\MainDiagnoses\Reading;
use App\Mail\ExternalClientInvitationMail;
use App\Models\Diagnosis;
use App\Models\DiagnosisType;
use App\Models\ExternalClient;
use App\Models\ExternalClientAnswer;
use App\Models\ExternalClientItem;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\User;
use App\Models\Value;
use App\Services\Diagnoses\DiagnosisCalculator;
use App\Services\ExternalClients\ExternalClientMeasurementService;
use App\Services\MainDiagnoses\StageMachine;
use App\Services\Materials\MaterialPipeline;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

/**
 * A sent, processed answer with its scores per value: value position => score.
 * Without a process item it answers the client's question for the values.
 *
 * @param  array<int, ?float>  $scores
 */
function sentClientAnswer(MainDiagnosis $main, ExternalClient $client, User $user, ?ExternalClientItem $processItem, string $text, array $scores): ExternalClientAnswer
{
    $material = Material::factory()->create([
        'context' => MaterialContext::ExternalClient,
        'status' => MaterialStatus::Processed,
        'organization_id' => $client->organization_id,
        'user_id' => $user->id,
    ]);
    $values = $main->valueSetVersion->snapshot['values'];

    foreach ($scores as $position => $score) {
        $material->scores()->create([
            'value_id' => $values[$position]['value_id'],
            'state' => $score === null ? ScoreResultState::NoData : ScoreResultState::Scored,
            'score' => $score,
            'detection_ids' => [],
        ]);
    }

    return ExternalClientAnswer::create([
        'main_diagnosis_id' => $main->id,
        'external_client_id' => $client->id,
        'user_id' => $user->id,
        'external_client_item_id' => $processItem?->id,
        'kind' => $processItem === null ? ExternalClientItemKind::Value : ExternalClientItemKind::Process,
        'process_id' => $processItem?->process_id,
        'question' => $processItem?->question ?? $client->valueQuestion(),
        'answer' => $text,
        'material_id' => $material->id,
        'submitted_at' => now(),
    ]);
}

/**
 * A running diagnosis with its client's two answers sent and calculated: the
 * value answer scores the first value 5, the process answer the first 3 and the second 4.
 *
 * @return array{0: MainDiagnosis, 1: ExternalClient, 2: User}
 */
function answeredClientDiagnosis(): array
{
    [$main, $client, $user] = diagnosisWithClient();
    $processItem = $client->items()->firstWhere('kind', ExternalClientItemKind::Process);

    sentClientAnswer($main, $client, $user, null, 'Mindig számíthattunk rájuk.', [0 => 5.0]);
    sentClientAnswer($main, $client, $user, $processItem, 'Gyors volt az ajánlat.', [0 => 3.0, 1 => 4.0]);
    app(DiagnosisCalculator::class)->recalculate($main->refresh());

    return [$main->refresh(), $client, $user];
}

function externalClientDiagnosisOf(MainDiagnosis $main): ?Diagnosis
{
    return Diagnosis::query()->where('parent_id', $main->diagnosis_id)->whereNotNull('external_client_id')->first();
}

it('builds one subdiagnosis per client from its answers, apart from the team\'s rows', function () {
    [$main, $client] = answeredClientDiagnosis();
    $firstValueId = $main->valueSetVersion->snapshot['values'][0]['value_id'];
    $processId = $client->items()->firstWhere('kind', ExternalClientItemKind::Process)->process_id;

    $subdiagnosis = externalClientDiagnosisOf($main);
    expect($subdiagnosis)
        ->external_client_id->toBe($client->id)
        ->level->toBe(DiagnosisLevel::Organization)
        ->diagnosis_type_id->toBe(DiagnosisType::byKey(DiagnosisType::EXTERNAL_CLIENT)->id)
        ->and($subdiagnosis->rows()->get()->map(fn ($row) => [$row->kind, $row->value_id, $row->process_id, (float) $row->score])->all())->toBe([
            // The value row reads both answers: (5 + 3) / 2; the process row its own answer: (3 + 4) / 2.
            [DiagnosisRowKind::Value, $firstValueId, null, 4.0],
            [DiagnosisRowKind::Process, null, $processId, 3.5],
        ]);

    // The team's row of the same value has no trace of it (35 · 35.7).
    $teamRow = $main->diagnosis->rows()->where('kind', DiagnosisRowKind::Value)->where('value_id', $firstValueId)->sole();
    expect($teamRow->score_state)->toBe(ScoreState::NoTrace);
});

it('drops the subdiagnosis of a client taken off the diagnosis', function () {
    [$main] = answeredClientDiagnosis();

    app(ExternalClientMeasurementService::class)->syncClients($main, []);
    app(DiagnosisCalculator::class)->recalculate($main->refresh());

    expect(externalClientDiagnosisOf($main))->toBeNull();
});

it('sums up the clients per value and process for the reading', function () {
    [$main, $client] = answeredClientDiagnosis();
    $firstValueId = $main->valueSetVersion->snapshot['values'][0]['value_id'];
    $processId = $client->items()->firstWhere('kind', ExternalClientItemKind::Process)->process_id;

    expect(app(ExternalClientMeasurementService::class)->summary($main))->toBe([
        'values' => [$firstValueId => ['mean' => 4.0, 'clients' => 1, 'answers' => 2]],
        'processes' => [$processId => ['mean' => 3.5, 'clients' => 1, 'answers' => 1]],
    ]);
});

it('shows the clients\' answers and where their users stand on the Ügyfélmérés tab, and sends a new link', function () {
    [$main, $client, $user] = answeredClientDiagnosis();
    actingAsAdmin();

    Livewire::test(ExternalClients::class, ['diagnosis' => $main->diagnosis])
        ->assertSee('Acme')
        ->assertSee('Mikor számíthattál ránk?')
        ->assertSee('Mindig számíthattunk rájuk.')
        ->assertSee(__('admin.external_clients.states.submitted'))
        ->call('resend', $client->id, $user->id)
        ->assertHasNoErrors();

    Mail::assertQueued(ExternalClientInvitationMail::class, 2);
});

it('has no Ügyfélmérés tab for a diagnosis without clients', function () {
    actingAsAdmin();
    $main = MainDiagnosis::factory()->create();

    $this->get(route('admin.main-diagnoses.external-clients', $main->diagnosis))->assertNotFound();
});

it('counts the clients\' users who answered on the Futás tab', function () {
    [$main] = answeredClientDiagnosis();
    actingAsAdmin(User::factory()->superadmin()->create());

    $this->get(route('admin.main-diagnoses.run', $main->diagnosis))
        ->assertOk()
        ->assertSee(__('admin.external_clients.run_count', ['answered' => 1, 'total' => 1]));
});

it('shows the clients\' view beside a value row of the reading', function () {
    [$main] = answeredClientDiagnosis();
    Queue::fake();
    app(StageMachine::class)->transition($main, MainDiagnosisStage::Reading);
    actingAsAdmin();
    $firstValueId = $main->valueSetVersion->snapshot['values'][0]['value_id'];
    $row = $main->diagnosis->rows()->where('kind', DiagnosisRowKind::Value)->where('value_id', $firstValueId)->sole();

    Livewire::test(Reading::class, ['diagnosis' => $main->diagnosis])
        ->call('selectRow', $row->id)
        ->assertSee(__('admin.external_clients.reading_figure', ['score' => '4,0', 'answers' => 2]));
});

it('labels the client\'s subdiagnosis and offers no loading for it', function () {
    [$main] = answeredClientDiagnosis();
    actingAsAdmin();
    $subdiagnosis = externalClientDiagnosisOf($main);

    expect($subdiagnosis->subjectLabel())->toBe('Ügyfél · Acme');
    $this->get(route('admin.main-diagnoses.subdiagnoses', $main->diagnosis))->assertOk()->assertSee('Ügyfél · Acme');
    $this->get(route('admin.diagnoses.show', $subdiagnosis))->assertOk()->assertDontSee(__('admin.diagnoses.aggregation.load'));
});

it('gives every value found in the answers a row when the client is measured on any value', function () {
    [$main, $client] = answeredClientDiagnosis();
    $values = $main->valueSetVersion->snapshot['values'];

    // Only the chosen first value has a row; the second, found in the process answer, has none.
    expect(externalClientDiagnosisOf($main)->valueRows()->pluck('value_id')->all())->toBe([$values[0]['value_id']]);

    // Any value instead of chosen ones (35 · 35.3).
    $client->items()->where('kind', ExternalClientItemKind::Value)->delete();
    $client->update(['measures_any_value' => true]);
    app(DiagnosisCalculator::class)->recalculate($main->refresh());

    $rows = externalClientDiagnosisOf($main)->valueRows()->get()->keyBy('value_id');
    expect($rows->keys()->sort()->values()->all())->toBe(collect([$values[0]['value_id'], $values[1]['value_id']])->sort()->values()->all())
        ->and((float) $rows[$values[0]['value_id']]->score)->toBe(4.0)
        ->and((float) $rows[$values[1]['value_id']]->score)->toBe(4.0)
        ->and($rows[$values[1]['value_id']]->aggregation_stats['found'])->toBeTrue();
});

it('adds what matched no value of the Értéktár as an AI-detected value, only for a client measured on any value', function (bool $anyValue) {
    Queue::fake();
    [$main, $client, $user] = diagnosisWithClient();
    $client->update(['measures_any_value' => $anyValue]);
    $answer = sentClientAnswer($main, $client, $user, null, 'Mindig kíváncsiak voltak ránk.', []);
    $answer->material->detections()->create(['quote' => 'Mindig kíváncsiak voltak ránk.', 'free_name' => 'Kíváncsiság', 'strength' => 3]);

    app(MaterialPipeline::class)->analyzed($answer->material);

    expect(Value::query()->where('name', 'Kíváncsiság')->where('origin', ValueOrigin::Ai)->exists())->toBe($anyValue);
})->with(['any value' => true, 'chosen values only' => false]);
