<?php

use App\Enums\DiagnosisStatus;
use App\Enums\ExperienceState;
use App\Enums\ScoreState;
use App\Enums\ValueOrigin;
use App\Livewire\Admin\Diagnoses\Show;
use App\Models\Diagnosis;
use App\Models\DiagnosisRow;
use App\Models\DiagnosisType;
use App\Models\Organization;
use App\Models\Value;
use App\Services\Diagnoses\DiagnosisService;
use App\Services\Values\ValueService;
use Livewire\Livewire;

function draftDiagnosis(string $type = 'workshop'): Diagnosis
{
    return app(DiagnosisService::class)->create([
        'name' => 'Workshop',
        'organization_id' => Organization::factory()->create()->id,
        'level' => 'organization',
        'diagnosis_type_id' => DiagnosisType::byKey($type)->id,
        'diagnosed_on' => today(),
    ]);
}

it('binds a value from the Értéktár with its current version', function () {
    actingAsAdmin();
    $diagnosis = draftDiagnosis();
    $value = app(ValueService::class)->create(['name' => 'Bátorság', 'high_examples' => ['a6'], 'low_examples' => ['a1']]);
    app(ValueService::class)->update($value, ['meanings' => ['Kimondom.']]);

    Livewire::test(Show::class, ['diagnosis' => $diagnosis])
        ->set('newValueName', 'Bátorság')
        ->call('addValue')
        ->assertSee('Hozzáadva: Bátorság.');

    $row = $diagnosis->valueRows()->sole();
    expect($row->value_id)->toBe($value->id)
        ->and($row->valueVersion->number)->toBe(2);
});

it('adds an unknown name to the Értéktár as found at a diagnosis', function () {
    actingAsAdmin();
    $diagnosis = draftDiagnosis();

    Livewire::test(Show::class, ['diagnosis' => $diagnosis])
        ->set('newValueName', 'Gondoskodás')
        ->call('addValue');

    $value = Value::query()->firstWhere('name', 'Gondoskodás');
    expect($value)
        ->origin->toBe(ValueOrigin::Diagnosis)
        ->origin_organization_id->toBe($diagnosis->organization_id)
        ->reviewed_at->toBeNull()
        ->has_endpoints->toBeFalse()
        ->and($diagnosis->valueRows()->sole()->valueVersion->number)->toBe(1);
});

it('refuses archived values, values of another organization and duplicates', function () {
    actingAsAdmin();
    $diagnosis = draftDiagnosis();
    Value::factory()->archived()->create(['name' => 'Kivezetett']);
    Value::factory()->from(ValueOrigin::Organization, Organization::factory()->create())->create(['name' => 'Másiké']);
    Value::factory()->create(['name' => 'Bátorság']);

    $component = Livewire::test(Show::class, ['diagnosis' => $diagnosis]);

    $component->set('newValueName', 'Kivezetett')->call('addValue')->assertSee('itt nem választható');
    $component->set('newValueName', 'Másiké')->call('addValue')->assertSee('itt nem választható');
    $component->set('newValueName', 'Bátorság')->call('addValue');
    $component->set('newValueName', 'Bátorság')->call('addValue')->assertSee('már szerepel ebben a diagnózisban');

    expect($diagnosis->valueRows()->count())->toBe(1);
});

it('requires the score on value rows and records "–" as no trace', function () {
    actingAsAdmin();
    $diagnosis = draftDiagnosis();
    $row = DiagnosisRow::factory()->for($diagnosis)->create();

    $component = Livewire::test(Show::class, ['diagnosis' => $diagnosis])
        ->call('editRow', $row->id)
        ->call('saveRow')
        ->assertHasErrors(['rowScore' => 'required'])
        ->set('rowScore', '7')
        ->call('saveRow')
        ->assertHasErrors(['rowScore'])
        ->set('rowScore', DiagnosisService::NO_TRACE)
        ->set('rowExperience', DiagnosisService::DONT_KNOW)
        ->call('saveRow')
        ->assertHasNoErrors();

    $row->refresh();
    expect($row->score)->toBeNull()
        ->and($row->score_state)->toBe(ScoreState::NoTrace)
        ->and($row->experience_state)->toBe(ExperienceState::DontKnow)
        ->and($row->scoreLabel())->toBe('–');
});

it('shows the gap between experience and score', function () {
    actingAsAdmin();
    $diagnosis = draftDiagnosis();
    $row = DiagnosisRow::factory()->for($diagnosis)->create();

    Livewire::test(Show::class, ['diagnosis' => $diagnosis])
        ->call('editRow', $row->id)
        ->set('rowScore', '2')
        ->set('rowExperience', '5')
        ->call('saveRow')
        ->assertHasNoErrors();

    expect($row->fresh()->gapLabel())->toBe('+3');
});

it('does not require a score in a Megélés diagnosis', function () {
    actingAsAdmin();
    $diagnosis = draftDiagnosis('experience');
    $row = DiagnosisRow::factory()->for($diagnosis)->create();

    Livewire::test(Show::class, ['diagnosis' => $diagnosis])
        ->call('editRow', $row->id)
        ->set('rowExperience', '4')
        ->call('saveRow')
        ->assertHasNoErrors();

    Livewire::test(Show::class, ['diagnosis' => $diagnosis])->call('changeStatus', 'pending_approval');

    expect($diagnosis->fresh()->status)->toBe(DiagnosisStatus::PendingApproval);
});

it('steps the status only when every value has a score, and logs each step', function () {
    $admin = actingAsAdmin();
    $diagnosis = draftDiagnosis();
    $row = DiagnosisRow::factory()->for($diagnosis)->create();

    Livewire::test(Show::class, ['diagnosis' => $diagnosis])
        ->call('changeStatus', 'pending_approval')
        ->assertSee('Nincs mérőszám ezeknél az értékeknél');
    expect($diagnosis->fresh()->status)->toBe(DiagnosisStatus::Draft);

    $row->update(['score' => 4, 'score_state' => ScoreState::Scored]);

    Livewire::test(Show::class, ['diagnosis' => $diagnosis])
        ->call('changeStatus', 'pending_approval')
        ->call('changeStatus', 'published')
        ->call('changeStatus', 'draft');

    expect($diagnosis->transitions()->pluck('to_status')->all())->toBe([
        DiagnosisStatus::Draft, DiagnosisStatus::PendingApproval, DiagnosisStatus::Published, DiagnosisStatus::Draft,
    ])->and($diagnosis->transitions()->latest('id')->first()->created_by)->toBe($admin->id);
});

it('logs the stage change of a main diagnosis, not the unchanged status (17 · 17.5)', function () {
    $setup = diagnosisTeam(peers: 1);
    actingAsAdmin($setup['admin']);
    $main = startMainDiagnosis($setup);

    Livewire::test(Show::class, ['diagnosis' => $main->diagnosis])
        ->assertSee('Vázlat → Meghívva')
        ->assertDontSee('Draft → Draft');
});

it('does not skip from draft to published', function () {
    actingAsAdmin();
    $diagnosis = draftDiagnosis();

    Livewire::test(Show::class, ['diagnosis' => $diagnosis])
        ->call('changeStatus', 'published')
        ->assertSee('Draft státuszból nem léptethető ide: Publikált.');
});

it('does not change rows of a published diagnosis', function () {
    actingAsAdmin();
    $diagnosis = Diagnosis::factory()->published()->create();
    $row = DiagnosisRow::factory()->for($diagnosis)->scored(4)->create();

    Livewire::test(Show::class, ['diagnosis' => $diagnosis])
        ->call('removeRow', $row->id)
        ->assertSee('A publikált diagnózis nem szerkeszthető.');

    $this->assertModelExists($row);
});

it('keeps the four brand base rows', function () {
    actingAsAdmin();
    $diagnosis = draftDiagnosis();

    Livewire::test(Show::class, ['diagnosis' => $diagnosis])
        ->call('removeRow', $diagnosis->treeRows()->first()->id)
        ->assertSee('A márkád alapja négy sora fix, nem törölhető.');

    expect($diagnosis->treeRows()->count())->toBe(4);
});

it('does not show diagnoses to customers', function () {
    actingAsCustomer(customerOf());
    $diagnosis = Diagnosis::factory()->create();

    $this->get(route('admin.diagnoses.show', $diagnosis))->assertForbidden();
});
