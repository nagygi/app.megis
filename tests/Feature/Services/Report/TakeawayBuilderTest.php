<?php

use App\Enums\ParticipantRole;
use App\Models\Consent;
use App\Models\DiagnosisText;
use App\Models\MainDiagnosis;
use App\Models\OccasionExperience;
use App\Models\User;
use App\Services\Diagnoses\ReadingService;
use App\Services\MainDiagnoses\ExperienceService;
use App\Services\MainDiagnoses\MainDiagnosisService;
use App\Services\Report\PublishService;
use App\Services\Report\ReportService;
use App\Services\Report\TakeawayBuilder;
use App\Services\Report\TakeawayText;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    Mail::fake();
});

/**
 * The peer's T4: one answer per value position, the tree at 4.
 *
 * @param  array<int, string>  $answers
 */
function peerStart(MainDiagnosis $main, User $peer, array $answers): void
{
    $main->participantFor($peer, ParticipantRole::Peer)->update(['started_at' => now()]);
    Consent::create(['user_id' => $peer->id, 'main_diagnosis_id' => $main->id, 'kind' => 'answers', 'text_version' => 'v', 'text' => 't', 'given_at' => now()]);
    $keys = collect($main->valueSetVersion->snapshot['values'])->pluck('key')->values();
    $rows = collect(app(ExperienceService::class)->rows($main->valueSetVersion->snapshot))
        ->mapWithKeys(fn (array $row) => [$row['key'] => str_starts_with($row['key'], 'value:') ? ($answers[$keys->search(substr($row['key'], 6))] ?? '4') : '4']);
    app(ExperienceService::class)->submit($main, $peer, $main->valueSetVersion, $rows->all());
}

/**
 * A situation of the peer on an occasion of the process: value position => answer.
 *
 * @param  array<int, string>  $answers
 */
function peerSituation(MainDiagnosis $main, User $peer, $occasion, array $answers): void
{
    $keys = collect($main->valueSetVersion->snapshot['values'])->pluck('key')->values();

    OccasionExperience::create([
        'process_occasion_id' => $occasion->id, 'user_id' => $peer->id, 'main_diagnosis_id' => $main->id,
        'answers' => collect($answers)->mapWithKeys(fn (string $answer, int $position) => ['value:'.$keys[$position] => $answer])->all(),
        'submitted_at' => now(),
    ]);
}

it('picks the blocks from the peer\'s own answers and tells them in their lines (29 · 29.1)', function () {
    [$setup, $main, $process] = calculatedDiagnosis();
    $peer = $setup['peers'][0];
    peerStart($main, $peer, [0 => '3', 1 => '5', 2 => '4']);
    // Three situations: the first value in all three, the second in two, the third in one only.
    peerSituation($main, $peer, occasionOn($process, 3), [0 => '6', 1 => '2', 2 => '4']);
    peerSituation($main, $peer, occasionOn($process, 2), [0 => '6', 1 => '3']);
    peerSituation($main, $peer, occasionOn($process, 1), [0 => '6']);

    $takeaway = app(TakeawayBuilder::class)->build($main->refresh(), $peer);
    $rows = collect($takeaway['rows']);

    expect($takeaway['strength']['name'])->toBe($rows[0]['name'])
        ->and(TakeawayText::strength($takeaway))->toBe('a helyzeteidben 6 · 3 helyzetből 3')
        // One situation is not a pattern (29 · 29.2).
        ->and($rows[2]['situations'])->toBeNull()
        ->and($takeaway['self_gap']['name'])->toBe($rows[0]['name'])
        ->and(TakeawayText::selfGap($takeaway))->toBe('az elején 3, a helyzeteidben 6 · ★ fő eltérés · Retró')
        // The only red: two or more lower in the situations.
        ->and($rows[1]['lower'])->toBeTrue()
        ->and($rows[0]['lower'])->toBeFalse()
        // The leader and one other peer: fewer than three, no mark of the others.
        ->and($takeaway['others_gap'])->toBeNull()
        ->and($rows->pluck('others')->filter()->all())->toBe([]);
});

it('falls back to the start without situations (29 · 29.1, 29.2)', function () {
    [$setup, $main] = calculatedDiagnosis();
    $peer = $setup['peers'][0];
    peerStart($main, $peer, [0 => '3', 1 => '5', 2 => '4']);

    $takeaway = app(TakeawayBuilder::class)->build($main->refresh(), $peer);

    expect($takeaway['strength']['name'])->toBe(collect($takeaway['rows'])[1]['name'])
        ->and(TakeawayText::strength($takeaway))->toBe('az elején 5')
        ->and($takeaway['self_gap'])->toBeNull();
});

it('tells where the own value came from and how often it shows (29 · 29.1)', function () {
    expect(TakeawayText::ownValue(['own_value' => ['team_count' => 5, 'mirror_on' => '2026-10-14', 'situations' => 2]]))->toBe('a csapat öt értékén túl · Értéktükör · 10.14. · 2 helyzetben')
        ->and(TakeawayText::ownValue(['own_value' => ['team_count' => 3]]))->toBe('a csapat három értékén túl');
});

it('keeps the Útravaló after the closing, its proposal written in markdown (29 · 29.2)', function () {
    [$setup, $main] = approvedReading();
    app(ReadingService::class)->saveText($main, 'recommendation', 'A **retrón** kezdenénk.', null, $setup['admin']);
    app(ReadingService::class)->approveText(DiagnosisText::query()->where('key', 'recommendation')->sole(), $setup['admin']);
    app(PublishService::class)->publish($main->refresh(), $setup['admin']);
    app(MainDiagnosisService::class)->close($main->refresh(), $setup['admin']);
    actingAsCustomer($setup['peers'][0]);
    $parameters = [$setup['organization'], $main->diagnosis];

    expect(app(ReportService::class)->snapshot($main)->data['takeaways'])->toHaveKey($setup['peers'][0]->id);
    $this->get(route('client.diagnosis.takeaway', $parameters))->assertOk()->assertSee('<strong>retrón</strong>', false);
    $this->get(route('client.diagnosis.show', $parameters))->assertOk()->assertSee(route('client.diagnosis.takeaway', $parameters));
});
