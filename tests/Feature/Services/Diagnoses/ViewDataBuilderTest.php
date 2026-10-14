<?php

use App\Enums\MainDiagnosisStage;
use App\Enums\ParticipantRole;
use App\Enums\StepMark;
use App\Models\Consent;
use App\Models\MainDiagnosis;
use App\Services\Diagnoses\DiagnosisCalculator;
use App\Services\Diagnoses\ThresholdPolicy;
use App\Services\Diagnoses\ViewDataBuilder;
use App\Services\MainDiagnoses\ExperienceService;
use App\Services\MainDiagnoses\MainDiagnosisService;
use App\Services\MainDiagnoses\StageMachine;
use App\Services\Organizations\OrganizationValueSetService;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => Queue::fake());

/**
 * Peers who started and sent their T4 with the same answer on every row.
 */
function peersAnswer(array $setup, MainDiagnosis $main, int $count, string $answer = '2'): void
{
    $rows = app(ExperienceService::class)->rows($main->valueSetVersion->snapshot);

    foreach (array_slice($setup['peers'], 0, $count) as $peer) {
        $main->participantFor($peer, ParticipantRole::Peer)->update(['started_at' => now()]);
        Consent::create(['user_id' => $peer->id, 'main_diagnosis_id' => $main->id, 'kind' => 'answers', 'text_version' => 'v', 'text' => 't', 'given_at' => now()]);
        app(ExperienceService::class)->submit($main, $peer, $main->valueSetVersion, collect($rows)->mapWithKeys(fn ($row) => [$row['key'] => $answer])->all());
    }
}

function teamWithPeers(int $peers): array
{
    $setup = diagnosisTeam(peers: $peers);
    $main = startMainDiagnosis($setup);
    confirmOwnValues($setup['leader']);
    $version = app(OrganizationValueSetService::class)->forOrganization($setup['organization'])->latestVersion;
    $rows = app(ExperienceService::class)->rows($version->snapshot);
    app(ExperienceService::class)->submit($main, $setup['leader'], $version, collect($rows)->mapWithKeys(fn ($row) => [$row['key'] => '6'])->all());
    $main->stepMarks()->create(['user_id' => $setup['leader']->id, 'step' => 'processes', 'mark' => StepMark::Skipped]);
    app(MainDiagnosisService::class)->start($main->refresh(), $setup['leader']);

    return [$setup, $main->refresh()];
}

it('hides the team experience below three peers (18 · 18.6)', function () {
    [$setup, $main] = teamWithPeers(3);
    scoredMaterial($main, null, [0 => 3, 1 => 3, 2 => 3]);
    peersAnswer($setup, $main, 2);
    app(DiagnosisCalculator::class)->recalculate($main);
    $main->refresh();

    $policy = app(ThresholdPolicy::class);
    $row = app(ViewDataBuilder::class)->team($main)[0];

    expect($policy->isMet($main))->toBeFalse()
        ->and($policy->missingPeers($main))->toBe(1)
        ->and($row['score'])->toBe(3.0)
        ->and($row['experience'])->toBeNull()
        ->and($row['gap'])->toBeNull();

    peersAnswer($setup, $main, 3);
    app(DiagnosisCalculator::class)->recalculate($main);
    $row = app(ViewDataBuilder::class)->team($main->refresh())[0];

    // The leader counts as one person: (6 + 2 + 2 + 2) / 4.
    expect($policy->isMet($main))->toBeTrue()
        ->and($row['experience'])->toBe(3.0)
        ->and($row['gap'])->toBe(0.0);
});

it('builds the individual view from the viewer\'s own materials (18 · 18.5)', function () {
    [$setup, $main] = teamWithPeers(3);
    $leader = $setup['leader'];
    scoredMaterial($main, null, [0 => 5, 1 => null, 2 => 2], $leader);
    scoredMaterial($main, null, [0 => 1, 1 => 1, 2 => 1]);
    app(DiagnosisCalculator::class)->recalculate($main);

    $builder = app(ViewDataBuilder::class);
    $rows = collect($builder->individual($main->refresh(), $leader));

    expect($rows->first()['score_state'])->toBe('insufficient_data')
        ->and($rows->first()['experience'])->toBe(6.0)
        ->and($rows->where('kind', 'tree_element'))->toHaveCount(4)
        ->and($rows->where('kind', 'area'))->toHaveCount(0);

    scoredMaterial($main, null, [0 => 3, 1 => null, 2 => 2], $leader);
    $rows = collect($builder->individual($main, $leader));

    expect($rows->first()['score'])->toBe(4.0)
        ->and($rows->first()['gap'])->toBe(2.0)
        ->and($rows[1]['score_state'])->toBe('no_trace');
});

it('joins the latest published diagnosis of each team in the organization view (18 · 18.5)', function () {
    [$setup, $main] = teamWithPeers(3);
    scoredMaterial($main, null, [0 => 4, 1 => 4, 2 => 4]);
    app(DiagnosisCalculator::class)->recalculate($main);
    app(StageMachine::class)->transition($main, MainDiagnosisStage::Reading, $setup['admin']);
    app(StageMachine::class)->transition($main->refresh(), MainDiagnosisStage::Published, $setup['admin']);

    $rows = collect(app(ViewDataBuilder::class)->organization($setup['organization']));

    expect($rows->where('kind', 'value'))->toHaveCount(3)
        ->and($rows->where('kind', 'process'))->toHaveCount(0)
        ->and($rows->where('kind', 'area'))->toHaveCount(5)
        ->and($rows->firstWhere('kind', 'value')['score'])->toBe(4.0)
        ->and($rows->firstWhere('kind', 'value')['diagnoses'])->toBe(1);
});
