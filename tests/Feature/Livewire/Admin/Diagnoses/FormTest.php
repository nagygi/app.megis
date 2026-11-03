<?php

use App\Enums\DiagnosisCreator;
use App\Enums\DiagnosisLevel;
use App\Enums\DiagnosisStatus;
use App\Livewire\Admin\Diagnoses\Form;
use App\Models\Diagnosis;
use App\Models\DiagnosisType;
use App\Models\Organization;
use App\Models\Process;
use App\Models\Team;
use App\Services\Diagnoses\DiagnosisCalculator;
use Livewire\Livewire;

it('creates a draft organization diagnosis with the four brand base rows', function () {
    $admin = actingAsAdmin();
    $organization = Organization::factory()->create();

    Livewire::test(Form::class)
        ->set('name', 'Őszi workshop')
        ->set('organization_id', $organization->id)
        ->set('diagnosis_type_id', DiagnosisType::byKey('workshop')->id)
        ->set('diagnosed_on', '2026-09-30')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $diagnosis = Diagnosis::query()->sole();
    expect($diagnosis)
        ->level->toBe(DiagnosisLevel::Organization)
        ->status->toBe(DiagnosisStatus::Draft)
        ->creator_kind->toBe(DiagnosisCreator::Admin)
        ->created_by->toBe($admin->id)
        ->and($diagnosis->treeRows()->count())->toBe(4)
        ->and($diagnosis->transitions()->sole())->to_status->toBe(DiagnosisStatus::Draft)->created_by->toBe($admin->id);
});

it('offers only measured processes on the Folyamat level and fills the rows with the organization\'s values (13 · 13.1)', function () {
    [$setup, $main, $process] = calculatedDiagnosis();
    $child = Process::factory()->accepted()->for($setup['organization'])->create(['name' => 'Retró-előkészítés', 'parent_id' => $process->id]);
    $candidate = Process::factory()->for($setup['organization'])->create(['name' => 'Még nem elfogadott']);
    actingAsAdmin($setup['admin']);

    Livewire::test(Form::class)
        ->set('name', 'Retró workshop')
        ->set('organization_id', $setup['organization']->id)
        ->set('level', DiagnosisLevel::Process->value)
        ->assertSee('Retró')
        ->assertDontSee('Retró-előkészítés')
        ->assertDontSee('Még nem elfogadott')
        ->set('process_id', $candidate->id)
        ->set('diagnosis_type_id', DiagnosisType::byKey(DiagnosisType::PROCESS)->id)
        ->call('save')
        ->assertHasErrors(['process_id'])
        ->set('process_id', $process->id)
        ->call('save')
        ->assertHasNoErrors();

    $diagnosis = Diagnosis::query()->where('name', 'Retró workshop')->sole();

    expect($diagnosis->creator_kind)->toBe(DiagnosisCreator::Admin)
        ->and($diagnosis->treeRows()->count())->toBe(0)
        ->and($diagnosis->valueRows()->count())->toBe(count($main->valueSetVersion->snapshot['values']))
        ->and($diagnosis->value_set_version_id)->not->toBeNull()
        ->and($child->exists)->toBeTrue();
});

it('keeps the data of a calculated process diagnosis to the calculation', function () {
    [$setup, $main, $process] = calculatedDiagnosis();
    app(DiagnosisCalculator::class)->recalculate($main);
    $diagnosis = processDiagnosisOf($main, $process);
    actingAsAdmin($setup['admin']);

    Livewire::test(Form::class, ['diagnosis' => $diagnosis])
        ->assertRedirect(route('admin.diagnoses.show', $diagnosis));
});

it('requires a team of the organization on team level', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $foreignTeam = Team::factory()->create();

    Livewire::test(Form::class)
        ->set('name', 'Csapatnap')
        ->set('organization_id', $organization->id)
        ->set('level', DiagnosisLevel::Team->value)
        ->set('diagnosis_type_id', DiagnosisType::byKey('workshop')->id)
        ->call('save')
        ->assertHasErrors(['team_id' => 'required'])
        ->set('team_id', $foreignTeam->id)
        ->call('save')
        ->assertHasErrors(['team_id' => 'exists']);
});

it('does not offer the team level for an individual', function () {
    actingAsAdmin();
    $individual = Organization::factory()->individual()->create();

    Livewire::test(Form::class)
        ->set('name', 'Egyéni')
        ->set('organization_id', $individual->id)
        ->set('level', DiagnosisLevel::Team->value)
        ->set('diagnosis_type_id', DiagnosisType::byKey('workshop')->id)
        ->call('save')
        ->assertHasErrors(['level' => 'not_in']);
});

it('requires an end date not before the start in period mode', function () {
    actingAsAdmin();

    Livewire::test(Form::class)
        ->set('name', 'Időszak')
        ->set('organization_id', Organization::factory()->create()->id)
        ->set('diagnosis_type_id', DiagnosisType::byKey('workshop')->id)
        ->set('isPeriod', true)
        ->set('diagnosed_on', '2026-09-30')
        ->call('save')
        ->assertHasErrors(['diagnosed_until' => 'required'])
        ->set('diagnosed_until', '2026-09-01')
        ->call('save')
        ->assertHasErrors(['diagnosed_until' => 'after_or_equal']);
});

it('keeps a published diagnosis read-only', function () {
    actingAsAdmin();
    $diagnosis = Diagnosis::factory()->published()->create(['name' => 'Eredeti']);

    Livewire::test(Form::class, ['diagnosis' => $diagnosis])
        ->set('name', 'Átírt')
        ->call('save')
        ->assertSee('A publikált diagnózis nem szerkeszthető.');

    expect($diagnosis->fresh()->name)->toBe('Eredeti');
});
