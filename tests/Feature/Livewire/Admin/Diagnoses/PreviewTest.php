<?php

use App\Enums\DiagnosisStatus;
use App\Livewire\Admin\Diagnoses\Preview;
use App\Models\Diagnosis;
use App\Models\DiagnosisRow;
use App\Models\MainDiagnosis;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Team;
use Livewire\Livewire;

it('frames the user\'s report and names who will read it (04 · 4.6)', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $user = customerOf($organization);
    $user->update(['last_name' => 'Alma', 'first_name' => 'Anna']);
    $diagnosis = Diagnosis::factory()->forUser($user, $organization)->create(['status' => DiagnosisStatus::PendingApproval]);

    $this->get(route('admin.diagnoses.preview', $diagnosis))->assertOk()
        ->assertSee('src="'.route('admin.main-diagnoses.preview', $diagnosis).'"', false)
        ->assertSee('Így látja: Alma Anna.')
        ->assertSee('Még nincs publikálva')
        ->assertSee('Publikálom');
});

it('counts the readers of a team diagnosis: its members and the organization\'s leaders', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $team = Team::factory()->for($organization)->create(['name' => 'Értékesítés']);
    customerOf($organization)->teams()->attach($team);
    $leader = customerOf($organization);
    Membership::query()->where('user_id', $leader->id)->update(['is_organization_leader' => true]);
    customerOf($organization);
    $diagnosis = Diagnosis::factory()->forTeam($team)->create();

    Livewire::test(Preview::class, ['diagnosis' => $diagnosis])
        ->assertSee('Így látják: a(z) Értékesítés csapat tagjai és a szervezet vezetői · 2 fő.')
        ->assertDontSee('Publikálom');
});

it('publishes from the preview and goes back to the diagnosis', function () {
    actingAsAdmin();
    $diagnosis = Diagnosis::factory()->create(['status' => DiagnosisStatus::PendingApproval]);
    DiagnosisRow::factory()->for($diagnosis)->scored(4.0)->create();

    Livewire::test(Preview::class, ['diagnosis' => $diagnosis])
        ->call('publish')
        ->assertRedirect(route('admin.diagnoses.show', $diagnosis));

    expect($diagnosis->refresh()->status)->toBe(DiagnosisStatus::Published)
        ->and($diagnosis->transitions()->latest('id')->first()->to_status)->toBe(DiagnosisStatus::Published);
});

it('renders the standalone report in the frame, clickable, before publishing', function () {
    actingAsAdmin();
    $diagnosis = Diagnosis::factory()->create(['status' => DiagnosisStatus::PendingApproval]);
    $row = DiagnosisRow::factory()->for($diagnosis)->scored(4.0)->create();

    $this->get(route('admin.main-diagnoses.preview', $diagnosis))->assertOk()
        ->assertSee('A ti diagnózisotok')
        ->assertSee(route('admin.main-diagnoses.preview', [$diagnosis, 'ertekek']), false);
    $this->get(route('admin.main-diagnoses.preview', [$diagnosis, 'ertekek', $row->id]))->assertOk();
    $this->get(route('admin.main-diagnoses.preview', [$diagnosis, 'zarolap']))->assertOk()->assertDontSee('Letöltöm · PDF');
    $this->get(route('admin.main-diagnoses.preview', [$diagnosis, 'osszefoglalo']))->assertNotFound();
});

it('has no preview page for a main diagnosis or a subdiagnosis', function () {
    actingAsAdmin();
    $main = MainDiagnosis::factory()->create();
    $subdiagnosis = Diagnosis::factory()->create(['organization_id' => $main->diagnosis->organization_id, 'parent_id' => $main->diagnosis_id]);

    $this->get(route('admin.diagnoses.preview', $main->diagnosis))->assertNotFound();
    $this->get(route('admin.diagnoses.preview', $subdiagnosis))->assertNotFound();
    $this->get(route('admin.main-diagnoses.preview', $subdiagnosis))->assertNotFound();
});

it('does not show the preview to customers', function () {
    actingAsCustomer(customerOf());
    $diagnosis = Diagnosis::factory()->create();

    $this->get(route('admin.diagnoses.preview', $diagnosis))->assertForbidden();
});
