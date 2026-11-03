<?php

use App\Enums\DiagnosisCreator;
use App\Enums\DiagnosisLevel;
use App\Enums\DiagnosisRowKind;
use App\Enums\DiagnosisStatus;
use App\Enums\MainDiagnosisStage;
use App\Enums\MaterialStatus;
use App\Enums\ParticipantRole;
use App\Enums\ScoreState;
use App\Models\Consent;
use App\Models\Diagnosis;
use App\Models\DiagnosisType;
use App\Models\Material;
use App\Models\OccasionExperience;
use App\Models\Process;
use App\Models\ProcessOccasion;
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
        // One verdict per occasion: "igen" against "részben" is a tie, so "részben"; the other occasion has no answer.
        ->and($diagnosis->aggregation['effectiveness'])->toBe(['yes' => 0, 'partial' => 1, 'no' => 0, 'none' => 1]);

    // A withdrawn peer leaves the calculation (25 · 25.6).
    Consent::query()->where('user_id', $peer->id)->update(['revoked_at' => now()]);
    app(DiagnosisCalculator::class)->recalculate($main);

    expect($row->refresh()->experience)->toBe(6.0);
});

it('gives a process row a calendar trace and the V11·c notes as traces, and keeps them by their key (18 · 18.2)', function () {
    [$setup, $main, $process] = calculatedDiagnosis();
    $first = ProcessOccasion::create(['process_id' => $process->id, 'occurred_at' => now()->subDays(9), 'source' => 'calendar']);
    $last = ProcessOccasion::create(['process_id' => $process->id, 'occurred_at' => now()->subDays(2), 'source' => 'calendar']);
    scoredMaterial($main, $first, [0 => 3, 1 => 3, 2 => 3]);
    scoredMaterial($main, $last, [0 => 4, 1 => 4, 2 => 4]);
    scoredMaterial($main, occasionOn($process, 5), [0 => 4, 1 => 4, 2 => 4]);
    $peer = $setup['peers'][0];
    $main->participantFor($peer, ParticipantRole::Peer)->update(['started_at' => now()]);
    $consent = Consent::create(['user_id' => $peer->id, 'main_diagnosis_id' => $main->id, 'kind' => 'answers', 'text_version' => 'v', 'text' => 't', 'given_at' => now()]);

    foreach ([[$setup['leader'], 'A retrón végre kimondtuk, mi csúszott.'], [$peer, 'Most először kérdezték, mit gondolok.']] as [$user, $note]) {
        OccasionExperience::create([
            'process_occasion_id' => $last->id, 'user_id' => $user->id, 'main_diagnosis_id' => $main->id,
            'answers' => [], 'effectiveness' => 'yes', 'note' => $note, 'submitted_at' => now(),
        ]);
    }

    app(DiagnosisCalculator::class)->recalculate($main);

    $row = $main->diagnosis->rows()->where('kind', DiagnosisRowKind::Process)->sole();
    $traces = $row->traces()->whereNotNull('auto_key')->get()->keyBy('source_type');
    expect($row->traces()->whereNotNull('auto_key')->count())->toBe(3)
        ->and($traces['calendar'])
        ->quote->toBe('A naptár szerint 2 alkalom volt '.$first->occurred_at->format('m.d.').' és '.$last->occurred_at->format('m.d.').' között.')
        ->role->toBeNull()
        ->process_occasion_id->toBe($last->id)
        ->and($row->traces()->where('source_type', 'experience')->pluck('role', 'quote')->all())->toBe([
            'A retrón végre kimondtuk, mi csúszott.' => 'leader',
            'Most először kérdezték, mit gondolok.' => 'peer',
        ]);

    // The admin's choice stays; a withdrawn peer's note leaves (25 · 25.6).
    $traces['calendar']->update(['excluded_at' => now()]);
    $consent->update(['revoked_at' => now()]);
    app(DiagnosisCalculator::class)->recalculate($main->refresh());

    expect($traces['calendar']->refresh()->excluded_at)->not->toBeNull()
        ->and($row->traces()->where('source_type', 'experience')->pluck('quote')->all())->toBe(['A retrón végre kimondtuk, mi csúszott.']);
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

it('moves the process point with a value row written by hand (13 · 13.3)', function () {
    [$setup, $main, $process] = calculatedDiagnosis();
    scoredMaterial($main, occasionOn($process, 2), [0 => 4, 1 => null, 2 => 2]);
    scoredMaterial($main, occasionOn($process, 1), [0 => 4, 1 => null, 2 => 2]);
    app(DiagnosisCalculator::class)->recalculate($main);
    $diagnosis = processDiagnosisOf($main, $process);

    expect($diagnosis->score)->toBe(3.0);

    app(DiagnosisService::class)->overrideRow($diagnosis->valueRows()->orderBy('position')->first(), '6', 'A workshop mást mutatott.', $setup['admin']);
    app(DiagnosisCalculator::class)->recalculate($main->refresh());

    // The written row counts with its own value: (6 + 2) / 2.
    expect($diagnosis->refresh()->score)->toBe(4.0)
        ->and($diagnosis->automatic_score)->toBe(4.0)
        ->and($diagnosis->aggregation['overrides'])->toHaveCount(1)
        ->and($diagnosis->aggregation['overrides'][0])->toMatchArray(['action' => 'override', 'automatic' => '4,0', 'value' => '6,0', 'by' => $setup['admin']->name]);

    // Back to the automatic value: the trace of the override stays (13 · 13.5).
    app(DiagnosisService::class)->resetRowOverride($diagnosis->valueRows()->orderBy('position')->first());
    app(DiagnosisCalculator::class)->recalculate($main->refresh());

    expect($diagnosis->refresh()->score)->toBe(3.0)
        ->and(collect($diagnosis->aggregation['overrides'])->pluck('action')->all())->toBe(['override', 'reset']);
});

it('gives each occasion the effectiveness most of its people answered, and counts people (13 · 13.3)', function () {
    [$setup, $main, $process] = calculatedDiagnosis();
    $first = occasionOn($process, 2);
    $second = occasionOn($process, 1);
    scoredMaterial($main, $first, [0 => 3, 1 => 3, 2 => 3]);
    scoredMaterial($main, $second, [0 => 3, 1 => 3, 2 => 3]);
    $key = 'value:'.$main->valueSetVersion->snapshot['values'][0]['key'];
    [$peer, $other] = $setup['peers'];

    $answers = [
        [$first, $setup['leader'], 'no', '5'], [$first, $peer, 'yes', '3'], [$first, $other, 'yes', 'dont_know'],
        [$second, $setup['leader'], 'partial', '6'], [$second, $peer, 'dont_know', '2'],
    ];

    foreach ($answers as [$occasion, $user, $effective, $answer]) {
        OccasionExperience::create([
            'process_occasion_id' => $occasion->id, 'user_id' => $user->id, 'main_diagnosis_id' => $main->id,
            'answers' => [$key => $answer], 'effectiveness' => $effective, 'submitted_at' => now(),
        ]);
    }

    // An answer outside this diagnosis on the same occasion does not count.
    OccasionExperience::create([
        'process_occasion_id' => $second->id, 'user_id' => $other->id, 'main_diagnosis_id' => null,
        'answers' => [$key => '1'], 'effectiveness' => 'no', 'submitted_at' => now(),
    ]);

    app(DiagnosisCalculator::class)->recalculate($main);

    $diagnosis = processDiagnosisOf($main, $process);
    $row = $diagnosis->valueRows()->orderBy('position')->first();

    expect($diagnosis->aggregation['effectiveness'])->toBe(['yes' => 1, 'partial' => 1, 'no' => 0, 'none' => 0])
        // One peer on two occasions is one "kitöltő"; "nem tudom" is no answer.
        ->and($row->aggregation_stats['peer_count'])->toBe(1)
        ->and($row->aggregation_stats['peers'])->toEqual(2.5)
        ->and($row->aggregation_stats['leader'])->toEqual(5.5);
});

it('lists the skipped occasions of the run once each, with the reason (13 · 13.5)', function () {
    [, $main, $process] = calculatedDiagnosis();
    $main->update(['starts_on' => today()->subDays(10)]);
    $used = occasionOn($process, 6);
    $failed = occasionOn($process, 5);
    $empty = occasionOn($process, 4);
    occasionOn($process, -3);
    scoredMaterial($main, $used, [0 => 3, 1 => 3, 2 => 3]);
    scoredMaterial($main, $used, [0 => 4, 1 => 4, 2 => 4]);

    foreach ([MaterialStatus::Failed, MaterialStatus::Analyzing] as $status) {
        scoredMaterial($main, $failed, [0 => 3])->update(['status' => $status]);
    }

    app(DiagnosisCalculator::class)->recalculate($main->refresh());

    $skipped = processDiagnosisOf($main, $process)->aggregation['skipped'];

    // The calendar's future occasion is not skipped yet.
    expect(collect($skipped)->pluck('reason', 'id')->all())->toBe([$failed->id => 'failed', $empty->id => 'no_material']);
});

it('warns on few occasions and on a divided row, as 04 · 4.5 does', function () {
    [, $main, $process] = calculatedDiagnosis();
    scoredMaterial($main, occasionOn($process, 2), [0 => 1, 1 => 3, 2 => null]);
    scoredMaterial($main, occasionOn($process, 1), [0 => 5, 1 => 3, 2 => null]);

    app(DiagnosisCalculator::class)->recalculate($main);

    $rows = processDiagnosisOf($main, $process)->valueRows()->orderBy('position')->get();

    expect($rows[0]->aggregation_stats['deviation'])->toEqual(2.0)
        ->and($rows[0]->aggregation_stats['warnings'])->toBe(['few_occasions', 'divided'])
        ->and($rows[1]->aggregation_stats['warnings'])->toBe(['few_occasions'])
        ->and($rows[2]->aggregation_stats['warnings'])->toBe([]);
});

it('names a process diagnosis without occasions and dates it from the start of the run', function () {
    [, $main, $process] = calculatedDiagnosis();

    app(DiagnosisCalculator::class)->recalculate($main);

    $diagnosis = processDiagnosisOf($main, $process);

    expect($diagnosis->name)->toBe('Retró · még nincs alkalom')
        ->and($diagnosis->diagnosed_on->toDateString())->toBe($main->starts_on->toDateString())
        ->and($diagnosis->creator_kind)->toBe(DiagnosisCreator::Automatic)
        ->and($diagnosis->value_set_version_id)->toBe($main->value_set_version_id)
        ->and($diagnosis->aggregation['period'])->toBeNull();
});

it('leaves a Folyamat diagnosis recorded by hand to the admin (13 · 13.1)', function () {
    [$setup, $main, $process] = calculatedDiagnosis();
    $manual = app(DiagnosisService::class)->create([
        'name' => 'Retró workshop', 'organization_id' => $setup['organization']->id, 'level' => DiagnosisLevel::Process,
        'process_id' => $process->id, 'diagnosis_type_id' => DiagnosisType::byKey(DiagnosisType::PROCESS)->id,
        'diagnosed_on' => today()->toDateString(), 'parent_id' => $main->diagnosis_id,
    ]);
    scoredMaterial($main, occasionOn($process, 1), [0 => 5, 1 => 5, 2 => 5]);

    app(DiagnosisCalculator::class)->recalculate($main);

    $calculated = Diagnosis::query()->where('parent_id', $main->diagnosis_id)->where('process_id', $process->id)->where('creator_kind', DiagnosisCreator::Automatic)->sole();
    $values = collect($main->valueSetVersion->snapshot['values'])->pluck('value_id')->sort()->values()->all();

    expect($manual->refresh()->name)->toBe('Retró workshop')
        ->and($manual->isCalculated())->toBeFalse()
        ->and($calculated->id)->not->toBe($manual->id)
        // Its rows are the organization's values, in the version in force (08 · 8.4).
        ->and($manual->valueRows()->pluck('value_id')->sort()->values()->all())->toBe($values)
        ->and($manual->value_set_version_id)->not->toBeNull()
        ->and($manual->treeRows()->count())->toBe(0);
});

it('removes the calculated diagnosis of a process taken off the main diagnosis', function () {
    [$setup, $main, $process] = calculatedDiagnosis();
    $other = Process::factory()->accepted()->for($setup['organization'])->create(['name' => 'Heti kör']);
    $main->processes()->sync([$process->id, $other->id]);
    app(DiagnosisCalculator::class)->recalculate($main->refresh());

    $main->processes()->sync([$process->id]);
    app(DiagnosisCalculator::class)->recalculate($main->refresh());

    expect(Diagnosis::query()->where('parent_id', $main->diagnosis_id)->where('level', DiagnosisLevel::Process)->pluck('process_id')->all())->toBe([$process->id]);
});

it('moves the calculated process diagnosis with the stage, logging it on its own (17 · 17.4)', function () {
    [$setup, $main, $process] = calculatedDiagnosis();
    app(DiagnosisCalculator::class)->recalculate($main);
    $diagnosis = processDiagnosisOf($main, $process);

    expect($diagnosis->status)->toBe(DiagnosisStatus::Draft);

    app(StageMachine::class)->transition($main, MainDiagnosisStage::Reading, $setup['admin']);

    expect($diagnosis->refresh()->status)->toBe(DiagnosisStatus::PendingApproval)
        ->and($diagnosis->transitions()->latest('id')->first()->to_status)->toBe(DiagnosisStatus::PendingApproval);
});
