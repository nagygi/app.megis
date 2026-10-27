<?php

use App\Enums\MainDiagnosisStage;
use App\Livewire\Admin\Diagnoses\Form as DiagnosisForm;
use App\Livewire\Admin\Diagnoses\Index;
use App\Livewire\Admin\MainDiagnoses\Form;
use App\Livewire\Admin\MainDiagnoses\Subdiagnoses;
use App\Livewire\Admin\Organizations\Diagnoses;
use App\Mail\DiagnosisLeaderInvitationMail;
use App\Models\Diagnosis;
use App\Models\DiagnosisType;
use App\Models\MainDiagnosis;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('starts a team diagnosis on A1 and sends the leader invitation', function () {
    Mail::fake();
    $setup = diagnosisTeam(peers: 2);
    actingAsAdmin($setup['admin']);

    Livewire::test(Form::class)
        ->set('organization_id', $setup['organization']->id)
        ->assertSet('contact_admin_id', $setup['admin']->id)
        ->set('team_id', $setup['team']->id)
        ->set('leader_id', $setup['leader']->id)
        ->assertSet('peers', [$setup['peers'][0]->id => true, $setup['peers'][1]->id => true])
        ->set('peers.'.$setup['peers'][1]->id, false)
        ->set('starts_on', today()->addWeek()->toDateString())
        ->assertSet('publishes_on', today()->addWeek()->addDays(42)->toDateString())
        ->assertSee('Saját értékek')
        ->call('sendInvitation')
        ->assertHasNoErrors()
        ->assertRedirect();

    $main = MainDiagnosis::query()->sole();
    expect($main->stage)->toBe(MainDiagnosisStage::Invited)
        ->and($main->peers()->pluck('user_id')->all())->toBe([$setup['peers'][0]->id]);

    Mail::assertQueued(DiagnosisLeaderInvitationMail::class);
});

it('names the default diagnosis name after the subject on A1 (17 · 17.1)', function () {
    $setup = diagnosisTeam(peers: 1);
    actingAsAdmin($setup['admin']);

    Livewire::test(Form::class)
        ->set('organization_id', $setup['organization']->id)
        ->assertSee('„Csapat · Termék · a kezdés napja”')
        ->set('subject', 'person')
        ->assertSee('„Név · Termék · a kezdés napja”')
        ->assertDontSee('„Csapat · Termék · a kezdés napja”');
});

it('tells organizations of the same name apart by their address on A1', function () {
    actingAsAdmin();
    Organization::factory()->create(['name' => 'Acme Kft.', 'slug' => 'acme']);
    Organization::factory()->create(['name' => 'Acme Kft.', 'slug' => 'acme-2']);
    Organization::factory()->create(['name' => 'Béta Bt.', 'slug' => 'beta']);

    Livewire::test(Form::class)
        ->assertSee('Acme Kft. · /acme')
        ->assertSee('Acme Kft. · /acme-2')
        ->assertSee('Béta Bt.')
        ->assertDontSee('Béta Bt. · /beta');
});

it('warns on A1 that below three peers there will be no team average and gap', function (int $peers, bool $warns) {
    $setup = diagnosisTeam(peers: $peers);
    actingAsAdmin($setup['admin']);

    $component = Livewire::test(Form::class)
        ->set('organization_id', $setup['organization']->id)
        ->set('team_id', $setup['team']->id)
        ->set('leader_id', $setup['leader']->id);

    $warns
        ? $component->assertSee('Három társ alatt nem lesz csapat-átlag és rés')
        : $component->assertDontSee('Három társ alatt nem lesz csapat-átlag és rés');
})->with([
    'two peers' => [2, true],
    'three peers' => [3, false],
]);

it('saves a draft without sending anything', function () {
    Mail::fake();
    $setup = diagnosisTeam();
    actingAsAdmin($setup['admin']);

    Livewire::test(Form::class, ['organization_id' => $setup['organization']->id])
        ->set('organization_id', $setup['organization']->id)
        ->set('team_id', $setup['team']->id)
        ->set('leader_id', $setup['leader']->id)
        ->call('saveDraft')
        ->assertHasNoErrors();

    expect(MainDiagnosis::query()->sole()->stage)->toBe(MainDiagnosisStage::Draft);
    Mail::assertNothingQueued();
});

it('creates a new team and a new leader', function () {
    Mail::fake();
    $setup = diagnosisTeam(peers: 0);
    actingAsAdmin($setup['admin']);

    Livewire::test(Form::class)
        ->set('organization_id', $setup['organization']->id)
        ->set('newTeam', true)
        ->set('new_team_name', 'Új csapat')
        ->set('newLeader', true)
        ->set('leader_last_name', 'Kovács')
        ->set('leader_first_name', 'Dóra')
        ->set('leader_email', 'dora@example.com')
        ->call('saveDraft')
        ->assertHasNoErrors();

    $main = MainDiagnosis::query()->sole();
    expect($main->diagnosis->team->name)->toBe('Új csapat')
        ->and($main->leader->email)->toBe('dora@example.com');
});

it('validates the subject, the team, the leader and the time window', function () {
    $setup = diagnosisTeam();
    actingAsAdmin($setup['admin']);
    $foreignTeam = Team::factory()->create();

    Livewire::test(Form::class)
        ->set('organization_id', $setup['organization']->id)
        ->set('team_id', $foreignTeam->id)
        ->set('leader_id', User::factory()->create()->id)
        ->set('starts_on', today()->addWeek()->toDateString())
        ->set('publishes_on', today()->toDateString())
        ->call('saveDraft')
        ->assertHasErrors(['team_id', 'leader_id', 'publishes_on']);

    expect(MainDiagnosis::query()->count())->toBe(0);
});

it('refuses a new leader who is already in the organization', function () {
    $setup = diagnosisTeam();
    actingAsAdmin($setup['admin']);

    Livewire::test(Form::class)
        ->set('organization_id', $setup['organization']->id)
        ->set('team_id', $setup['team']->id)
        ->set('newLeader', true)
        ->set('leader_last_name', 'Már')
        ->set('leader_first_name', 'Tag')
        ->set('leader_email', $setup['peers'][0]->email)
        ->call('saveDraft')
        ->assertHasErrors(['leader_email']);
});

it('says when the product has no diagnosis flow', function () {
    $setup = diagnosisTeam(product: Product::SNAPSHOT_EXTERNAL);
    actingAsAdmin($setup['admin']);

    Livewire::test(Form::class)
        ->set('organization_id', $setup['organization']->id)
        ->assertSee('Ehhez a termékhez még nincs diagnózis-folyamat.');
});

it('greys out the invitation while the organization values are missing for a non-leader', function () {
    $setup = diagnosisTeam(organizationLeader: false, withValues: false);
    actingAsAdmin($setup['admin']);

    Livewire::test(Form::class)
        ->set('organization_id', $setup['organization']->id)
        ->set('team_id', $setup['team']->id)
        ->set('leader_id', $setup['leader']->id)
        ->assertSee('A szervezet értékeit előbb a szervezet vezetője vagy az admin adja meg');
});

it('changes the time window and the peers after the invitation, but not the team', function () {
    $setup = diagnosisTeam(peers: 2);
    $main = startMainDiagnosis($setup);
    actingAsAdmin($setup['admin']);

    Livewire::test(Form::class, ['diagnosis' => $main->diagnosis])
        ->assertSet('peers', [$setup['peers'][0]->id => true, $setup['peers'][1]->id => true])
        ->set('publishes_on', today()->addWeeks(8)->toDateString())
        ->set('peers.'.$setup['peers'][0]->id, false)
        ->set('show_peer_quotes', false)
        ->call('saveDraft')
        ->assertHasNoErrors();

    $main->refresh();
    expect($main->publishes_on->toDateString())->toBe(today()->addWeeks(8)->toDateString())
        ->and($main->show_peer_quotes)->toBeFalse()
        ->and($main->peers()->pluck('user_id')->all())->toBe([$setup['peers'][1]->id]);
});

it('picks the organization leader as the leader of a team (17 · 17.2)', function () {
    $setup = diagnosisTeam();
    actingAsAdmin($setup['admin']);

    Livewire::test(Form::class)
        ->set('organization_id', $setup['organization']->id)
        ->assertSet('leader_id', $setup['leader']->id)
        ->assertDontSee('a szervezet értékeit és a márkád alapját csak olvassa')
        ->set('leader_id', $setup['peers'][0]->id)
        ->assertSee('a szervezet értékeit és a márkád alapját csak olvassa')
        ->set('subject', 'person')
        ->assertSet('leader_id', null);
});

it('replaces the leader behind its edit button, without an e-mail, and invites them on request', function () {
    $setup = diagnosisTeam(peers: 1);
    $main = startMainDiagnosis($setup);
    $newLeader = $setup['peers'][0];
    actingAsAdmin($setup['admin']);

    Livewire::test(Form::class, ['diagnosis' => $main->diagnosis])
        ->assertDontSee('Lecserélem a vezetőt')
        ->assertDontSee('Elküldöm a meghívót az új vezetőnek')
        ->set('changingLeader', true)
        ->set('replacementLeaderId', $newLeader->id)
        ->assertSee('a szervezet értékeit és a márkád alapját csak olvassa')
        ->call('changeLeader')
        ->assertHasNoErrors();

    expect($main->refresh()->leader_id)->toBe($newLeader->id);
    Mail::assertNotQueued(DiagnosisLeaderInvitationMail::class, fn ($mail) => $mail->hasTo($newLeader->email));

    Livewire::test(Form::class, ['diagnosis' => $main->diagnosis])
        ->assertSee('Elküldöm a meghívót az új vezetőnek')
        ->call('inviteLeader');

    Mail::assertQueued(DiagnosisLeaderInvitationMail::class, fn ($mail) => $mail->hasTo($newLeader->email));

    Livewire::test(Form::class, ['diagnosis' => $main->refresh()->diagnosis])
        ->assertDontSee('Elküldöm a meghívót az új vezetőnek')
        ->call('close');

    expect($main->refresh()->stage)->toBe(MainDiagnosisStage::Closed);
});

it('lists the stage on the diagnoses list and filters by it', function () {
    $setup = diagnosisTeam();
    $invited = startMainDiagnosis($setup);
    $manual = Diagnosis::factory()->create(['name' => 'Kézi workshop']);
    actingAsAdmin($setup['admin']);

    Livewire::withQueryParams(['nezet' => 'lista'])->test(Index::class)
        ->assertSee($invited->diagnosis->name)
        ->assertSee('Meghívva')
        ->assertSee('Kézi workshop')
        ->set('filters.stage', MainDiagnosisStage::Invited->value)
        ->assertSee($invited->diagnosis->name)
        ->assertDontSee('Kézi workshop');
});

it('shows the organization\'s diagnoses on its tab', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup, invite: false);
    actingAsAdmin($setup['admin']);

    Livewire::test(Diagnoses::class, ['organization' => $setup['organization']])
        ->assertSee($main->diagnosis->name)
        ->assertSee('Vázlat')
        ->assertSee(route('admin.main-diagnoses.create', ['szervezet' => $setup['organization']->id]), false);
});

it('lists the subdiagnoses and links a manual diagnosis to the main diagnosis', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsAdmin($setup['admin']);

    Livewire::test(DiagnosisForm::class)
        ->set('name', 'Interjú')
        ->set('organization_id', $setup['organization']->id)
        ->set('diagnosis_type_id', DiagnosisType::byKey('workshop')->id)
        ->set('parent_id', $main->diagnosis_id)
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(Subdiagnoses::class, ['diagnosis' => $main->diagnosis])->assertSee('Interjú');
});

it('does not offer the main diagnosis type for a manual diagnosis', function () {
    $setup = diagnosisTeam();
    actingAsAdmin($setup['admin']);

    Livewire::test(DiagnosisForm::class)
        ->set('name', 'Kézi')
        ->set('organization_id', $setup['organization']->id)
        ->set('diagnosis_type_id', DiagnosisType::byKey(DiagnosisType::MAIN)->id)
        ->call('save')
        ->assertHasErrors(['diagnosis_type_id']);
});

it('keeps guests and customers out of A1', function () {
    $setup = diagnosisTeam();

    $this->get(route('admin.main-diagnoses.create'))->assertRedirect(route('admin.login'));

    actingAsCustomer($setup['leader']);
    $this->get(route('admin.main-diagnoses.create'))->assertForbidden();
});
