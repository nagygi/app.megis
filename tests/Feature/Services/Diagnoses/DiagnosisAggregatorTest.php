<?php

use App\Enums\DiagnosisCreator;
use App\Enums\ExperienceState;
use App\Enums\ScoreState;
use App\Enums\TreeElement;
use App\Exceptions\DiagnosisException;
use App\Livewire\Admin\Diagnoses\Show;
use App\Models\Diagnosis;
use App\Models\DiagnosisRow;
use App\Models\DiagnosisType;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use App\Models\Value;
use App\Services\Diagnoses\DiagnosisAggregator;
use App\Services\Diagnoses\DiagnosisService;
use Livewire\Livewire;

/**
 * A team member with a published user diagnosis scoring the given value.
 */
function teamMemberWithDiagnosis(Team $team, Value $value, ?float $score, ?float $experience = null, string $date = '2026-09-01'): User
{
    $user = User::factory()->create();
    Membership::factory()->for($team->organization)->for($user)->create();
    $team->users()->attach($user);

    $diagnosis = Diagnosis::factory()->forUser($user, $team->organization)->published()->create(['diagnosed_on' => $date]);
    $row = DiagnosisRow::factory()->for($diagnosis)->for($value);

    ($score === null ? $row->noTrace() : $row->scored($score, $experience))->create();

    return $user;
}

function teamDiagnosis(Team $team): Diagnosis
{
    return app(DiagnosisService::class)->create([
        'name' => 'Csapat összesítés',
        'organization_id' => $team->organization_id,
        'level' => 'team',
        'team_id' => $team->id,
        'diagnosis_type_id' => DiagnosisType::byKey('workshop')->id,
        'diagnosed_on' => '2026-10-01',
    ]);
}

it('averages the latest published user diagnoses with equal weight', function () {
    $team = Team::factory()->create();
    $courage = Value::factory()->create(['name' => 'Bátorság']);
    $courage->recordVersion();
    teamMemberWithDiagnosis($team, $courage, 4, 5);
    teamMemberWithDiagnosis($team, $courage, 5, 6);
    $third = teamMemberWithDiagnosis($team, $courage, 1, null, '2026-03-01');
    // A newer diagnosis of the same user replaces the older one.
    $newer = Diagnosis::factory()->forUser($third, $team->organization)->published()->create(['diagnosed_on' => '2026-09-15']);
    DiagnosisRow::factory()->for($newer)->for($courage)->scored(6)->create();

    $preview = app(DiagnosisAggregator::class)->preview(teamDiagnosis($team));
    $row = collect($preview['rows'])->firstWhere('value_id', $courage->id);

    expect($row['n'])->toBe(3)
        ->and($row['score'])->toBe(['n' => 3, 'mean' => 5.0, 'deviation' => 0.82])
        ->and($row['experience']['mean'])->toBe(5.5)
        ->and($row['warnings'])->toBe([])
        ->and($preview['usable_count'])->toBe(3);
});

it('leaves out "–", "nem tudom" and diagnoses that do not count', function () {
    $team = Team::factory()->create();
    $value = Value::factory()->create();
    teamMemberWithDiagnosis($team, $value, 4);
    teamMemberWithDiagnosis($team, $value, null);
    teamMemberWithDiagnosis($team, $value, 2, null, '2025-06-01');
    $withoutDiagnosis = User::factory()->create(['first_name' => 'Nincs', 'last_name' => 'Diagnózis']);
    Membership::factory()->for($team->organization)->for($withoutDiagnosis)->create();
    $team->users()->attach($withoutDiagnosis);
    $inactive = User::factory()->create();
    Membership::factory()->for($team->organization)->for($inactive)->inactive()->create();
    $team->users()->attach($inactive);
    // Another team's member does not count.
    teamMemberWithDiagnosis(Team::factory()->for($team->organization)->create(), $value, 1);

    $preview = app(DiagnosisAggregator::class)->preview(teamDiagnosis($team));
    $row = collect($preview['rows'])->firstWhere('value_id', $value->id);

    expect($row['score']['mean'])->toBe(4.0)
        ->and($row['n'])->toBe(2)
        ->and($row['warnings'])->toBe(['few_users'])
        ->and(collect($preview['excluded'])->pluck('reason')->sort()->values()->all())->toBe(['inactive', 'no_diagnosis', 'too_old'])
        ->and($preview['active_count'])->toBe(4)
        ->and($preview['usable_count'])->toBe(2)
        ->and($preview['low_coverage'])->toBeFalse();
});

it('takes the score from the latest scored diagnosis and the experience from the latest Megélés', function () {
    $team = Team::factory()->create();
    $value = Value::factory()->create();
    $user = teamMemberWithDiagnosis($team, $value, 4, 2, '2026-06-01');
    // A newer Megélés (V5/T4) has only the experience; it must not hide the score.
    $experience = Diagnosis::factory()->forUser($user, $team->organization)->ofType('experience')->published()->create(['diagnosed_on' => '2026-09-20']);
    DiagnosisRow::factory()->for($experience)->for($value)->create(['experience' => 6, 'experience_state' => ExperienceState::Answered]);

    $preview = app(DiagnosisAggregator::class)->preview(teamDiagnosis($team));
    $row = collect($preview['rows'])->firstWhere('value_id', $value->id);

    expect($row['score']['mean'])->toBe(4.0)
        ->and($row['experience']['mean'])->toBe(6.0)
        ->and($row['n'])->toBe(1)
        ->and(collect($preview['sources'])->pluck('metric')->sort()->values()->all())->toBe(['experience', 'score'])
        ->and($preview['usable_count'])->toBe(1);
});

it('marks a divided row and low coverage', function () {
    $team = Team::factory()->create();
    $value = Value::factory()->create();
    teamMemberWithDiagnosis($team, $value, 1);
    teamMemberWithDiagnosis($team, $value, 6);
    teamMemberWithDiagnosis($team, $value, 1);
    foreach (range(1, 4) as $i) {
        $user = User::factory()->create();
        Membership::factory()->for($team->organization)->for($user)->create();
        $team->users()->attach($user);
    }

    $preview = app(DiagnosisAggregator::class)->preview(teamDiagnosis($team));

    expect(collect($preview['rows'])->firstWhere('value_id', $value->id)['warnings'])->toBe(['divided'])
        ->and($preview['low_coverage'])->toBeTrue();
});

it('replaces the rows, saves the method and marks rows changed by hand later', function () {
    $admin = actingAsAdmin();
    $team = Team::factory()->create();
    $value = Value::factory()->create();
    $value->recordVersion();
    teamMemberWithDiagnosis($team, $value, 4, 3);
    teamMemberWithDiagnosis($team, $value, 5, 3);
    $diagnosis = teamDiagnosis($team);
    $manual = DiagnosisRow::factory()->for($diagnosis)->scored(2)->create();

    Livewire::test(Show::class, ['diagnosis' => $diagnosis])
        ->call('previewAggregation')
        ->assertSee('Előnézet')
        ->call('loadAggregation')
        ->assertSee('Az aggregált adatok betöltve.');

    $diagnosis->refresh();
    $row = $diagnosis->valueRows()->sole();
    expect($diagnosis->creator_kind)->toBe(DiagnosisCreator::Aggregated)
        ->and($diagnosis->aggregation['loaded_by'])->toBe($admin->name)
        ->and($diagnosis->aggregation['window'])->toBe(['from' => '2025-10-01', 'to' => '2026-10-01'])
        ->and($diagnosis->aggregation['calculation'])->toContain('számtani átlag')
        ->and($row->score)->toBe(4.5)
        ->and($row->score_state)->toBe(ScoreState::Scored)
        ->and($row->experience_state)->toBe(ExperienceState::Answered)
        ->and($row->scoreLabel())->toBe('4,5')
        ->and($row->valueVersion->number)->toBe(1)
        ->and($row->manually_modified)->toBeFalse();
    $this->assertModelMissing($manual);
    expect($diagnosis->treeRows()->count())->toBe(4)
        ->and($diagnosis->treeRows()->where('tree_element', TreeElement::Root)->sole())->score->toBeNull()->isAggregated()->toBeFalse();

    Livewire::test(Show::class, ['diagnosis' => $diagnosis])
        ->call('editRow', $row->id)
        ->set('rowScore', '3')
        ->call('saveRow');

    expect($row->fresh()->manually_modified)->toBeTrue();
});

it('offers aggregation only on team and organization level', function () {
    $user = User::factory()->create();
    $diagnosis = Diagnosis::factory()->forUser($user, Organization::factory()->create())->create();

    app(DiagnosisAggregator::class)->preview($diagnosis);
})->throws(DiagnosisException::class);
