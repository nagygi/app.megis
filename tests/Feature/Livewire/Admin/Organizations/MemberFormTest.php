<?php

use App\Enums\MembershipStatus;
use App\Enums\TokenPurpose;
use App\Livewire\Admin\Organizations\MemberForm;
use App\Mail\ClientInvitationMail;
use App\Mail\OrganizationAddedMail;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('records a new user without sending anything by default', function () {
    Mail::fake();
    actingAsAdmin();
    $organization = Organization::factory()->create();

    Livewire::test(MemberForm::class, ['organization' => $organization])
        ->set('email', 'uj@ceg.hu')
        ->set('isEmployee', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.organizations.members', $organization));

    $membership = Membership::query()->sole();
    expect($membership->status)->toBe(MembershipStatus::Added)
        ->and($membership->user->email)->toBe('uj@ceg.hu')
        ->and($membership->user->activated_at)->toBeNull();
    Mail::assertNothingQueued();
});

it('sends a 14-day registration link when "send now" is on', function () {
    Mail::fake();
    $actor = actingAsAdmin();
    $organization = Organization::factory()->create();

    Livewire::test(MemberForm::class, ['organization' => $organization])
        ->set('email', 'uj@ceg.hu')
        ->set('isOrganizationLeader', true)
        ->set('sendNow', true)
        ->call('save')
        ->assertHasNoErrors();

    $membership = Membership::query()->sole();
    expect($membership->status)->toBe(MembershipStatus::Invited);
    $token = $membership->user->tokens()->where('purpose', TokenPurpose::ClientInvitation)->sole();
    expect($token->organization_id)->toBe($organization->id)
        ->and($token->expires_at->diffInDays(now(), true))->toEqualWithDelta(14, 0.01);
    Mail::assertQueued(ClientInvitationMail::class, fn (ClientInvitationMail $mail) => $mail->hasTo('uj@ceg.hu') && $mail->inviter->is($actor));
});

it('adds an existing account to another organization with a notice instead of a new account', function () {
    Mail::fake();
    actingAsAdmin();
    $existing = customerOf();
    $organization = Organization::factory()->create();

    Livewire::test(MemberForm::class, ['organization' => $organization])
        ->set('email', strtoupper($existing->email))
        ->set('isPartner', true)
        ->set('sendNow', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(User::query()->where('email', $existing->email)->count())->toBe(1);
    $membership = $organization->memberships()->sole();
    expect($membership->user_id)->toBe($existing->id)
        ->and($membership->status)->toBe(MembershipStatus::Active)
        ->and($membership->is_partner)->toBeTrue();
    Mail::assertQueued(OrganizationAddedMail::class, fn (OrganizationAddedMail $mail) => $mail->hasTo($existing->email));
});

it('requires at least one tag', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();

    Livewire::test(MemberForm::class, ['organization' => $organization])
        ->set('email', 'uj@ceg.hu')
        ->call('save')
        ->assertHasErrors(['tags' => 'Válassz legalább egy címkét.']);

    expect(Membership::query()->count())->toBe(0);
});

it('rejects someone who is already a member', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $member = customerOf($organization);

    Livewire::test(MemberForm::class, ['organization' => $organization])
        ->set('email', $member->email)
        ->set('isEmployee', true)
        ->call('save')
        ->assertHasErrors(['email' => 'Ez a felhasználó már tagja a szervezetnek.']);
});

it('stops at the headcount limit, partners included', function () {
    actingAsAdmin();
    $organization = Organization::factory()->withHeadcountLimit(2)->create();
    Membership::factory()->for($organization)->create();
    Membership::factory()->partner()->added()->for($organization)->create();

    Livewire::test(MemberForm::class, ['organization' => $organization])
        ->set('email', 'harmadik@ceg.hu')
        ->set('isEmployee', true)
        ->call('save')
        ->assertHasErrors(['email' => 'Elértétek a létszám limitet (2 / 2).']);
});

it('does not count inactive users towards the limit', function () {
    actingAsAdmin();
    $organization = Organization::factory()->withHeadcountLimit(1)->create();
    Membership::factory()->inactive()->for($organization)->create();

    Livewire::test(MemberForm::class, ['organization' => $organization])
        ->set('email', 'uj@ceg.hu')
        ->set('isEmployee', true)
        ->call('save')
        ->assertHasNoErrors();
});

it('assigns teams and team leaders within the organization', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $ops = Team::factory()->for($organization)->create();
    $sales = Team::factory()->for($organization)->create();
    $foreignTeam = Team::factory()->create();

    Livewire::test(MemberForm::class, ['organization' => $organization])
        ->set('email', 'uj@ceg.hu')
        ->set('isEmployee', true)
        ->set('teamIds', [$ops->id, $sales->id, $foreignTeam->id])
        ->set('leaderTeamIds', [$ops->id])
        ->call('save')
        ->assertHasNoErrors();

    $teams = User::query()->firstWhere('email', 'uj@ceg.hu')->teams()->get()->keyBy('id');
    expect($teams->keys()->sort()->values()->all())->toBe(collect([$ops->id, $sales->id])->sort()->values()->all())
        ->and((bool) $teams[$ops->id]->pivot->is_leader)->toBeTrue()
        ->and((bool) $teams[$sales->id]->pivot->is_leader)->toBeFalse();
});

it('preselects the team when coming from its row', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $team = Team::factory()->for($organization)->create();

    Livewire::withQueryParams(['csapat' => $team->id])
        ->test(MemberForm::class, ['organization' => $organization])
        ->assertSet('teamIds', [$team->id]);
});

it('makes the only user of a private person its leader', function () {
    actingAsAdmin();
    $organization = Organization::factory()->individual()->create();

    Livewire::test(MemberForm::class, ['organization' => $organization])
        ->set('email', 'maganszemely@example.com')
        ->call('save')
        ->assertHasNoErrors();

    expect($organization->memberships()->sole()->is_organization_leader)->toBeTrue();
});

it('updates position, tags and teams of a member', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $team = Team::factory()->for($organization)->create();
    $membership = Membership::factory()->for($organization)->create();

    Livewire::test(MemberForm::class, ['organization' => $organization, 'membership' => $membership])
        ->set('position', 'Ügyvezető')
        ->set('isOrganizationLeader', true)
        ->set('teamIds', [$team->id])
        ->call('save')
        ->assertHasNoErrors();

    expect($membership->fresh())
        ->position->toBe('Ügyvezető')
        ->is_organization_leader->toBeTrue()
        ->and($membership->user->teams()->pluck('teams.id')->all())->toBe([$team->id]);
});
