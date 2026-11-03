<?php

use App\Events\TeamMemberAdded;
use App\Livewire\Admin\Organizations\TeamForm;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

it('creates a team with a name unique within the organization, then goes back to the list', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();

    Livewire::test(TeamForm::class, ['organization' => $organization])
        ->set('name', 'Value Core Team')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.organizations.teams', $organization));

    expect($organization->teams()->sole()->name)->toBe('Value Core Team');
});

it('rejects a team name already used in the organization', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    Team::factory()->for($organization)->create(['name' => 'Ops']);

    Livewire::test(TeamForm::class, ['organization' => $organization])
        ->set('name', 'Ops')
        ->call('save')
        ->assertHasErrors(['name' => 'Ilyen nevű csapat már van a szervezetben.']);
});

it('allows the same team name in another organization', function () {
    actingAsAdmin();
    Team::factory()->create(['name' => 'Ops']);
    $organization = Organization::factory()->create();

    Livewire::test(TeamForm::class, ['organization' => $organization])
        ->set('name', 'Ops')
        ->call('save')
        ->assertHasNoErrors();
});

it('creates a team with users already in the organization and a leader', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $anna = Membership::factory()->for($organization)->create()->user;
    $bela = Membership::factory()->for($organization)->create()->user;

    Livewire::test(TeamForm::class, ['organization' => $organization])
        ->set('name', 'Ops')
        ->call('addMember', $anna->id)
        ->call('addMember', $bela->id)
        ->set('leaderUserIds', [$anna->id])
        ->call('save')
        ->assertHasNoErrors();

    $users = $organization->teams()->sole()->users()->get()->keyBy('id');
    expect($users->keys()->sort()->values()->all())->toBe(collect([$anna->id, $bela->id])->sort()->values()->all())
        ->and((bool) $users[$anna->id]->pivot->is_leader)->toBeTrue()
        ->and((bool) $users[$bela->id]->pivot->is_leader)->toBeFalse();
});

it('adds only a user of the organization who is not inactive', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $foreigner = Membership::factory()->create()->user;
    $inactive = Membership::factory()->inactive()->for($organization)->create()->user;

    Livewire::test(TeamForm::class, ['organization' => $organization])
        ->call('addMember', $foreigner->id)
        ->call('addMember', $inactive->id)
        ->assertSet('userIds', []);
});

it('drops the leader mark of a member taken out', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $anna = Membership::factory()->for($organization)->create()->user;

    Livewire::test(TeamForm::class, ['organization' => $organization])
        ->call('addMember', $anna->id)
        ->set('leaderUserIds', [$anna->id])
        ->call('removeMember', $anna->id)
        ->assertSet('userIds', [])
        ->assertSet('leaderUserIds', []);
});

it('offers the users not in the team yet, narrowed by the search', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $anna = Membership::factory()->for($organization)->for(User::factory()->create(['last_name' => 'Kovács', 'first_name' => 'Anna']))->create()->user;
    $bela = Membership::factory()->for($organization)->for(User::factory()->create(['last_name' => 'Szabó', 'first_name' => 'Béla']))->create()->user;

    Livewire::test(TeamForm::class, ['organization' => $organization])
        ->assertSee($anna->email)
        ->assertSee($bela->email)
        ->set('memberSearch', $bela->name)
        ->assertDontSee($anna->email)
        ->assertSee($bela->email)
        ->call('addMember', $bela->id)
        ->assertSet('memberSearch', '')
        ->assertSee($anna->email)
        ->assertSee(__('admin.teams.remove_member', ['name' => $bela->name]));
});

it('adds the first match of the typed search on Enter', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    Membership::factory()->for($organization)->for(User::factory()->create(['last_name' => 'Ács', 'first_name' => 'Anna']))->create();
    $bela = Membership::factory()->for($organization)->for(User::factory()->create(['last_name' => 'Szabó', 'first_name' => 'Béla']))->create()->user;

    Livewire::test(TeamForm::class, ['organization' => $organization])
        ->call('addFirstCandidate', 'szab')
        ->assertSet('userIds', [$bela->id])
        ->assertSet('memberSearch', '');
});

it('edits the members of a team', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $team = Team::factory()->for($organization)->create();
    $anna = Membership::factory()->for($organization)->create()->user;
    $bela = Membership::factory()->for($organization)->create()->user;
    $cili = Membership::factory()->for($organization)->create()->user;
    $team->users()->attach([$anna->id => ['is_leader' => true], $bela->id => ['is_leader' => false]]);

    Livewire::test(TeamForm::class, ['organization' => $organization, 'team' => $team])
        ->assertSet('userIds', [$anna->id, $bela->id])
        ->assertSet('leaderUserIds', [$anna->id])
        ->call('removeMember', $bela->id)
        ->call('addMember', $cili->id)
        ->set('leaderUserIds', [$cili->id])
        ->call('save')
        ->assertHasNoErrors();

    $users = $team->users()->get()->keyBy('id');
    expect($users->keys()->sort()->values()->all())->toBe(collect([$anna->id, $cili->id])->sort()->values()->all())
        ->and((bool) $users[$anna->id]->pivot->is_leader)->toBeFalse()
        ->and((bool) $users[$cili->id]->pivot->is_leader)->toBeTrue();
});

it('offers an inactive user only while already in the team', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $team = Team::factory()->for($organization)->create();
    $stayed = Membership::factory()->inactive()->for($organization)->create()->user;
    $outsider = Membership::factory()->inactive()->for($organization)->create()->user;
    $team->users()->attach($stayed);

    Livewire::test(TeamForm::class, ['organization' => $organization])
        ->assertDontSee($stayed->email)
        ->assertDontSee($outsider->email);

    Livewire::test(TeamForm::class, ['organization' => $organization, 'team' => $team])
        ->assertSee($stayed->email)
        ->assertDontSee($outsider->email)
        ->call('save')
        ->assertHasNoErrors();

    expect($team->users()->pluck('users.id')->all())->toBe([$stayed->id]);
});

it('announces only the newly added members to a running team diagnosis', function () {
    actingAsAdmin();
    Event::fake([TeamMemberAdded::class]);
    $organization = Organization::factory()->create();
    $team = Team::factory()->for($organization)->create();
    $old = Membership::factory()->for($organization)->create()->user;
    $new = Membership::factory()->for($organization)->create()->user;
    $team->users()->attach($old);

    Livewire::test(TeamForm::class, ['organization' => $organization, 'team' => $team])
        ->call('addMember', $new->id)
        ->call('save')
        ->assertHasNoErrors();

    Event::assertDispatchedTimes(TeamMemberAdded::class, 1);
    Event::assertDispatched(TeamMemberAdded::class, fn (TeamMemberAdded $event) => $event->team->is($team) && $event->user->is($new));
});

it('opens a team only under its own organization', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $otherTeam = Team::factory()->create();

    $response = $this->get(route('admin.organizations.teams.edit', [$organization, $otherTeam]));

    $response->assertNotFound();
});

it('has no team form for a private person', function () {
    actingAsAdmin();
    $organization = Organization::factory()->individual()->create();

    $response = $this->get(route('admin.organizations.teams.create', $organization));

    $response->assertNotFound();
});
