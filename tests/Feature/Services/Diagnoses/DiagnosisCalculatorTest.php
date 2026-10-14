<?php

use App\Enums\DiagnosisRowKind;
use App\Enums\MainDiagnosisStage;
use App\Enums\ParticipantRole;
use App\Enums\ScoreState;
use App\Models\Consent;
use App\Models\Material;
use App\Models\OccasionExperience;
use App\Models\Process;
use App\Models\Product;
use App\Services\Diagnoses\DiagnosisCalculator;
use App\Services\Diagnoses\DiagnosisService;
use App\Services\MainDiagnoses\StageMachine;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

it('builds a process diagnosis from the occasions, one point per occasion (13 · 13.2, 13.3)', function () {
    [, $main, $process] = calculatedDiagnosis();
    $first = occasionOn($process, 7);
    $second = occasionOn($process, 1);
    // Two materials of one occasion give one point: their mean.
    scoredMaterial($main, $first, [0 => 4, 1 => null, 2 => 2]);
    scoredMaterial($main, $first, [0 => 6, 1 => null, 2 => 2]);
    scoredMaterial($main, $second, [0 => 3, 1 => null, 2 => 4]);

    app(DiagnosisCalculator::class)->recalculate($main);

    $diagnosis = processDiagnosisOf($main, $process);
    $rows = $diagnosis->valueRows()->orderBy('position')->get();

    expect($rows[0]->score)->toBe(4.0)
        ->and($rows[0]->aggregation_stats['n'])->toBe(2)
        ->and($rows[0]->aggregation_stats['deviation'])->toEqual(1.0)
        ->and($rows[1]->score_state)->toBe(ScoreState::NoTrace)
        ->and($rows[2]->score)->toBe(3.0)
        // The point: the mean of the rows with a number.
        ->and($diagnosis->score)->toBe(3.5)
        ->and($diagnosis->score_state)->toBe(ScoreState::Scored)
        ->and($diagnosis->name)->toContain('Retró · ')
        ->and($diagnosis->aggregation['occasions'])->toBe(2)
        ->and($diagnosis->treeRows()->count())->toBe(0);
});

it('leaves the point empty with fewer occasions than the minimum (13 · 13.3)', function () {
    [, $main, $process] = calculatedDiagnosis();
    scoredMaterial($main, occasionOn($process, 1), [0 => 5, 1 => 5, 2 => 5]);

    app(DiagnosisCalculator::class)->recalculate($main);

    $diagnosis = processDiagnosisOf($main, $process);
    $row = $main->diagnosis->rows()->where('kind', DiagnosisRowKind::Process)->sole();

    expect($diagnosis->score_state)->toBe(ScoreState::InsufficientData)
        ->and($diagnosis->scoreLabel())->toBe('–')
        ->and($row->score_state)->toBe(ScoreState::InsufficientData)
        ->and($row->aggregation_stats['insufficient'])->toBeTrue();
});

it('takes one occasion in the internal snapshot, marked as such', function () {
    [, $main, $process] = calculatedDiagnosis(Product::SNAPSHOT_INTERNAL);
    scoredMaterial($main, occasionOn($process, 1), [0 => 5, 1 => 4, 2 => 3]);

    app(DiagnosisCalculator::class)->recalculate($main);

    $diagnosis = processDiagnosisOf($main, $process);

    expect($diagnosis->score)->toBe(4.0)
        ->and($diagnosis->aggregation['single_occasion'])->toBeTrue()
        ->and($main->diagnosis->rows()->where('kind', DiagnosisRowKind::Area)->count())->toBe(0);
});

it('counts the occasions of a subprocess in its parent', function () {
    [$setup, $main, $process] = calculatedDiagnosis();
    $child = Process::factory()->accepted()->for($setup['organization'])->create(['parent_id' => $process->id]);
    scoredMaterial($main, occasionOn($process, 3), [0 => 2, 1 => 2, 2 => 2]);
    scoredMaterial($main, occasionOn($child, 1), [0 => 4, 1 => 4, 2 => 4]);

    app(DiagnosisCalculator::class)->recalculate($main);

    expect(processDiagnosisOf($main, $process)->score)->toBe(3.0);
});

it('scores the value rows of the main diagnosis from all its materials (18 · 18.4)', function () {
    [, $main, $process] = calculatedDiagnosis();
    scoredMaterial($main, occasionOn($process, 2), [0 => 5, 1 => null, 2 => 2]);
    scoredMaterial($main, null, [0 => 4, 1 => null, 2 => 3]);
    // An unclassified material counts too (13 · 13.2).
    scoredMaterial($main, null, [0 => 4.5, 1 => null, 2 => 4]);

    app(DiagnosisCalculator::class)->recalculate($main);
    $main->refresh();

    $rows = $main->diagnosis->valueRows()->orderBy('position')->get();

    expect($rows[0]->score)->toBe(4.5)
        ->and($rows[0]->aggregation_stats['n'])->toBe(3)
        ->and($rows[1]->score_state)->toBe(ScoreState::NoTrace)
        ->and($rows[2]->score)->toBe(3.0)
        ->and($main->diagnosis->rows()->where('kind', DiagnosisRowKind::Area)->count())->toBe(5)
        ->and($main->diagnosis->treeRows()->count())->toBe(4)
        // The "–" row is not a measuring point; the process has too few occasions.
        ->and($main->calculation['measuring_points'])->toBe(2)
        ->and($main->calculation['ring'])->toBe(63)
        ->and($main->calculation['step'])->toBe(3)
        ->and($main->calculation['points'])->toBe(8)
        ->and($main->calculation['max'])->toBe(12);
});

it('keeps a row written by hand and updates only its automatic value (13 · 13.4)', function () {
    [$setup, $main, $process] = calculatedDiagnosis();
    scoredMaterial($main, null, [0 => 2, 1 => 2, 2 => 2]);
    app(DiagnosisCalculator::class)->recalculate($main);

    $row = $main->diagnosis->valueRows()->orderBy('position')->first();
    app(DiagnosisService::class)->overrideRow($row, '5', 'A felvételek nem mutatják a heti rutint.', $setup['admin']);

    scoredMaterial($main, null, [0 => 4, 1 => 4, 2 => 4]);
    app(DiagnosisCalculator::class)->recalculate($main);
    $row->refresh();

    expect($row->score)->toBe(5.0)
        ->and($row->automatic_score)->toBe(3.0)
        ->and($row->override_reason)->toBe('A felvételek nem mutatják a heti rutint.')
        ->and($row->overridden_by)->toBe($setup['admin']->id)
        ->and($row->differsFromAutomatic())->toBeTrue();

    app(DiagnosisService::class)->resetRowOverride($row);

    expect($row->refresh()->score)->toBe(3.0)
        ->and($row->overridden_at)->toBeNull();
});

it('averages the occasion experiences per value, leader and peers apart (13 · 13.3)', function () {
    [$setup, $main, $process] = calculatedDiagnosis();
    $occasion = occasionOn($process, 1);
    scoredMaterial($main, $occasion, [0 => 3, 1 => 3, 2 => 3]);
    scoredMaterial($main, occasionOn($process, 2), [0 => 3, 1 => 3, 2 => 3]);
    $key = 'value:'.$main->valueSetVersion->snapshot['values'][0]['key'];
    $peer = $setup['peers'][0];
    $main->participantFor($peer, ParticipantRole::Peer)->update(['started_at' => now()]);
    Consent::create(['user_id' => $peer->id, 'main_diagnosis_id' => $main->id, 'kind' => 'answers', 'text_version' => 'v', 'text' => 't', 'given_at' => now()]);

    foreach ([[$setup['leader'], '6', 'yes'], [$peer, '4', 'partial']] as [$user, $answer, $effective]) {
        OccasionExperience::create([
            'process_occasion_id' => $occasion->id, 'user_id' => $user->id, 'main_diagnosis_id' => $main->id,
            'answers' => [$key => $answer], 'effectiveness' => $effective, 'submitted_at' => now(),
        ]);
    }

    app(DiagnosisCalculator::class)->recalculate($main);

    $diagnosis = processDiagnosisOf($main, $process);
    $row = $diagnosis->valueRows()->orderBy('position')->first();

    expect($row->experience)->toBe(5.0)
        ->and($row->aggregation_stats['leader'])->toEqual(6.0)
        ->and($row->aggregation_stats['peers'])->toEqual(4.0)
        ->and($row->aggregation_stats['peer_count'])->toBe(1)
        ->and($row->gap())->toBe(2.0)
        ->and($diagnosis->aggregation['effectiveness'])->toBe(['yes' => 1, 'partial' => 1, 'no' => 0]);

    // A withdrawn peer leaves the calculation (25 · 25.6).
    Consent::query()->where('user_id', $peer->id)->update(['revoked_at' => now()]);
    app(DiagnosisCalculator::class)->recalculate($main);

    expect($row->refresh()->experience)->toBe(6.0);
});

it('does not change a published diagnosis (13 · 13.4)', function () {
    [$setup, $main] = calculatedDiagnosis();
    scoredMaterial($main, null, [0 => 2, 1 => 2, 2 => 2]);
    app(DiagnosisCalculator::class)->recalculate($main);
    app(StageMachine::class)->transition($main, MainDiagnosisStage::Reading, $setup['admin']);
    app(StageMachine::class)->transition($main->refresh(), MainDiagnosisStage::Published, $setup['admin']);

    scoredMaterial($main, null, [0 => 6, 1 => 6, 2 => 6]);
    app(DiagnosisCalculator::class)->recalculate($main->refresh());

    expect($main->diagnosis->valueRows()->orderBy('position')->first()->score)->toBe(2.0);
});
