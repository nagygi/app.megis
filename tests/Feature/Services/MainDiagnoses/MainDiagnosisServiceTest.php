<?php

use App\Enums\DiagnosisLevel;
use App\Enums\DiagnosisStatus;
use App\Enums\MainDiagnosisStage;
use App\Enums\MainDiagnosisSubject;
use App\Enums\MaterialContext;
use App\Enums\MembershipStatus;
use App\Enums\ParticipantRole;
use App\Enums\StepMark;
use App\Events\TeamMemberAdded;
use App\Exceptions\DiagnosisException;
use App\Mail\DiagnosisLeaderInvitationMail;
use App\Mail\DiagnosisPeerInvitationMail;
use App\Models\Diagnosis;
use App\Models\DiagnosisType;
use App\Models\Material;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Product;
use App\Models\User;
use App\Services\Diagnoses\DiagnosisService;
use App\Services\Engine\AutomaticDiagnosisBuilder;
use App\Services\MainDiagnoses\ExperienceService;
use App\Services\MainDiagnoses\MainDiagnosisService;
use App\Services\MainDiagnoses\StageMachine;
use App\Services\Organizations\MembershipService;
use App\Services\Organizations\OrganizationValueSetService;
use Illuminate\Support\Facades\Mail;

/**
 * Finishes every leader step before V9 (23 · 23.4).
 */
function completeLeaderSteps($main, User $leader): void
{
    confirmOwnValues($leader);
    $version = app(OrganizationValueSetService::class)->forOrganization($main->diagnosis->organization)->latestVersion;
    $rows = app(ExperienceService::class)->rows($version->snapshot);
    app(ExperienceService::class)->submit($main, $leader, $version, collect($rows)->mapWithKeys(fn ($row) => [$row['key'] => '4'])->all());
    $main->stepMarks()->create(['user_id' => $leader->id, 'step' => 'processes', 'mark' => StepMark::Skipped]);
}

it('starts a team diagnosis as a draft with the product, the leader and the peers', function () {
    $setup = diagnosisTeam(peers: 2);
    $main = startMainDiagnosis($setup, invite: false, overrides: ['excluded_peer_ids' => [$setup['peers'][1]->id]]);

    $diagnosis = $main->diagnosis;
    expect($diagnosis->type->key)->toBe(DiagnosisType::MAIN)
        ->and($diagnosis->level)->toBe(DiagnosisLevel::Team)
        ->and($diagnosis->team_id)->toBe($setup['team']->id)
        ->and($diagnosis->name)->toBe($setup['team']->name.' · Teljes · '.today()->addWeek()->format('Y.m.d.'))
        ->and($diagnosis->status)->toBe(DiagnosisStatus::Draft)
        ->and($main->stage)->toBe(MainDiagnosisStage::Draft)
        ->and($main->product_key)->toBe(Product::FULL)
        ->and($main->leader_id)->toBe($setup['leader']->id)
        ->and($main->peers()->pluck('user_id')->all())->toBe([$setup['peers'][0]->id])
        ->and($main->participants()->where('role', ParticipantRole::Peer)->whereNotNull('excluded_at')->pluck('user_id')->all())->toBe([$setup['peers'][1]->id]);

    Mail::assertNothingQueued();
});

it('keeps the product of the start when the organization changes product', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup, invite: false);

    $setup['organization']->update(['product_id' => startingProduct(Product::SNAPSHOT_INTERNAL)->id]);

    expect($main->refresh()->product_key)->toBe(Product::FULL)
        ->and($main->product_name)->toBe('Teljes');
});

it('cannot start a diagnosis for a product without a diagnosis flow', function () {
    $setup = diagnosisTeam(product: Product::SNAPSHOT_EXTERNAL);

    startMainDiagnosis($setup, invite: false);
})->throws(DiagnosisException::class, 'Ehhez a termékhez még nincs diagnózis-folyamat.');

it('adds a new leader to the organization and tags them as the team leader', function () {
    $setup = diagnosisTeam();

    $main = startMainDiagnosis($setup, invite: false, overrides: [
        'leader_id' => null,
        'new_leader' => ['first_name' => 'Anna', 'last_name' => 'Kiss', 'email' => 'anna.kiss@example.com'],
    ]);

    $leader = $main->leader;
    expect($leader->email)->toBe('anna.kiss@example.com')
        ->and(Membership::query()->whereBelongsTo($setup['organization'])->where('user_id', $leader->id)->value('status'))->toBe(MembershipStatus::Added)
        ->and($setup['team']->leaders()->pluck('users.id')->all())->toContain($leader->id);
});

it('sends the leader invitation as the first gate', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);

    expect($main->stage)->toBe(MainDiagnosisStage::Invited)
        ->and($main->invited_at)->not->toBeNull()
        ->and($main->diagnosis->transitions()->latest('id')->first()->to_stage)->toBe(MainDiagnosisStage::Invited);

    Mail::assertQueued(DiagnosisLeaderInvitationMail::class, fn ($mail) => $mail->hasTo($setup['leader']->email) && $mail->token === null);
});

it('sends a registration link to a leader without an account', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup, overrides: [
        'leader_id' => null,
        'new_leader' => ['first_name' => 'Béla', 'last_name' => 'Új', 'email' => 'bela@example.com'],
    ]);

    Mail::assertQueued(DiagnosisLeaderInvitationMail::class, fn ($mail) => $mail->hasTo('bela@example.com') && $mail->token !== null);
    expect(Membership::query()->where('user_id', $main->leader_id)->value('status'))->toBe(MembershipStatus::Invited);
});

it('keeps the invitation back without a contact or, for a non-leader, without organization values', function () {
    $setup = diagnosisTeam(organizationLeader: false, withValues: false);
    $setup['organization']->update(['contact_admin_id' => null]);
    $main = startMainDiagnosis($setup, invite: false);

    expect(app(MainDiagnosisService::class)->invitationBlockers($main))->toHaveCount(2);

    app(MainDiagnosisService::class)->invite($main, $setup['admin']);
})->throws(DiagnosisException::class);

it('moves to filling on the leader\'s first view', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);

    app(MainDiagnosisService::class)->opened($main, $main->leaderParticipant());

    expect($main->refresh()->stage)->toBe(MainDiagnosisStage::Filling)
        ->and($main->leaderParticipant()->opened_at)->not->toBeNull();
});

it('starts running only when every step is done, recording the value set version and inviting the peers', function () {
    $setup = diagnosisTeam(peers: 2);
    $main = startMainDiagnosis($setup);
    $service = app(MainDiagnosisService::class);

    expect(fn () => $service->start($main, $setup['leader']))->toThrow(DiagnosisException::class);

    completeLeaderSteps($main, $setup['leader']);
    $service->start($main->refresh(), $setup['leader']);

    $main->refresh();
    expect($main->stage)->toBe(MainDiagnosisStage::Running)
        ->and($main->value_set_version_id)->toBe($setup['organization']->valueSet->latestVersion->id)
        ->and($main->peers()->whereNotNull('invited_at')->count())->toBe(2);

    Mail::assertQueued(DiagnosisPeerInvitationMail::class, 2);
});

it('only lets the leader start the diagnosis', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);

    app(MainDiagnosisService::class)->start($main, $setup['peers'][0]);
})->throws(DiagnosisException::class);

it('makes a new team member a peer while the diagnosis runs, until reading starts', function () {
    $setup = diagnosisTeam(peers: 1);
    $main = startMainDiagnosis($setup);
    completeLeaderSteps($main, $setup['leader']);
    app(MainDiagnosisService::class)->start($main->refresh(), $setup['leader']);

    $newcomer = User::factory()->create();
    $membership = app(MembershipService::class)->add($setup['organization'], [
        'email' => $newcomer->email,
        'is_organization_leader' => false,
        'is_employee' => true,
        'is_partner' => false,
        'teams' => [$setup['team']->id => false],
    ], $setup['admin'], sendNow: true);

    expect($main->peers()->where('user_id', $membership->user_id)->whereNotNull('invited_at')->exists())->toBeTrue();
    Mail::assertQueued(DiagnosisPeerInvitationMail::class, fn ($mail) => $mail->hasTo($newcomer->email));

    app(StageMachine::class)->transition($main->refresh(), MainDiagnosisStage::Reading);
    $late = User::factory()->create();
    Membership::factory()->for($setup['organization'])->for($late)->create();
    TeamMemberAdded::dispatch($setup['team'], $late);

    expect($main->participants()->where('user_id', $late->id)->exists())->toBeFalse();
});

it('replaces the leader without an e-mail and keeps the old leader\'s row', function () {
    $setup = diagnosisTeam(peers: 2);
    $main = startMainDiagnosis($setup);
    $newLeader = $setup['peers'][0];

    app(MainDiagnosisService::class)->changeLeader($main, $newLeader);

    $main->refresh();
    expect($main->leader_id)->toBe($newLeader->id)
        ->and($main->participants()->where('user_id', $setup['leader']->id)->whereNotNull('replaced_at')->exists())->toBeTrue()
        ->and($main->peers()->where('user_id', $newLeader->id)->exists())->toBeFalse()
        ->and($main->leaderParticipant()->invited_at)->toBeNull();

    Mail::assertNotQueued(DiagnosisLeaderInvitationMail::class, fn ($mail) => $mail->hasTo($newLeader->email));
});

it('closes from any stage and a diagnosis closed before reading stays a draft', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);

    app(MainDiagnosisService::class)->close($main, $setup['admin']);

    expect($main->refresh()->stage)->toBe(MainDiagnosisStage::Closed)
        ->and($main->closed_at)->not->toBeNull()
        ->and($main->diagnosis->status)->toBe(DiagnosisStatus::Draft);
});

it('refuses stage changes that skip the flow', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup, invite: false);

    app(StageMachine::class)->transition($main, MainDiagnosisStage::Published);
})->throws(DiagnosisException::class);

it('moves the subdiagnoses with the main diagnosis and keeps them from moving alone', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    completeLeaderSteps($main, $setup['leader']);
    $experience = Diagnosis::query()->where('parent_id', $main->diagnosis_id)->firstOrFail();

    app(MainDiagnosisService::class)->start($main->refresh(), $setup['leader']);
    app(StageMachine::class)->transition($main->refresh(), MainDiagnosisStage::Reading);

    expect($main->diagnosis->refresh()->status)->toBe(DiagnosisStatus::PendingApproval)
        ->and($experience->refresh()->status)->toBe(DiagnosisStatus::PendingApproval);

    expect(fn () => app(DiagnosisService::class)->transition($experience, DiagnosisStatus::Published))->toThrow(DiagnosisException::class)
        ->and(fn () => app(DiagnosisService::class)->transition($main->diagnosis, DiagnosisStatus::Published))->toThrow(DiagnosisException::class);
});

it('makes an engine diagnosis of a material a subdiagnosis of its main diagnosis', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    $material = Material::factory()->create([
        'context' => MaterialContext::Mirror,
        'organization_id' => $setup['organization']->id,
        'user_id' => $setup['leader']->id,
        'parent_diagnosis_id' => $main->diagnosis_id,
    ]);

    $diagnosis = app(AutomaticDiagnosisBuilder::class)->build(
        $material,
        $setup['leader'],
        $setup['organization'],
        DiagnosisType::byKey(DiagnosisType::MIRROR),
        'Tükör',
        now(),
    );

    expect($diagnosis->parent_id)->toBe($main->diagnosis_id)
        ->and($diagnosis->status)->toBe(DiagnosisStatus::Draft);
});

it('starts a diagnosis for a private person with the own values instead of the organization values', function () {
    $organization = Organization::factory()->individual()->create([
        'product_id' => startingProduct(Product::SNAPSHOT_INTERNAL)->id,
        'contact_admin_id' => User::factory()->admin()->create()->id,
    ]);
    $person = User::factory()->create();
    Membership::factory()->for($organization)->for($person)->organizationLeader()->create();

    $main = app(MainDiagnosisService::class)->create([
        'organization_id' => $organization->id,
        'subject' => MainDiagnosisSubject::Team->value,
        'publishes_on' => today()->addWeeks(2)->toDateString(),
        'show_peer_quotes' => true,
    ], User::factory()->admin()->create());

    expect($main->subject)->toBe(MainDiagnosisSubject::Individual)
        ->and($main->diagnosis->level)->toBe(DiagnosisLevel::Organization)
        ->and($main->leader_id)->toBe($person->id)
        ->and($main->hasPeers())->toBeFalse()
        ->and(array_map(fn ($step) => $step->value, $main->leaderSteps()))->toBe(['own_values', 'brand', 'experience', 'processes', 'visibility']);
});
