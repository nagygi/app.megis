<?php

use App\Events\TeamMemberAdded;
use App\Livewire\Admin\Organizations\Teams;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Team;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

it('creates a team with a name unique within the organization', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();

    Livewire::test(Teams::class, ['organization' => $organization])
        ->call('create')
        ->set('name', 'Value Core Team')
        ->call('save')
        ->assertHasNoErrors();

    expect($organization->teams()->sole()->name)->toBe('Value Core Team');
});

it('rejects a team name already used in the organization', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    Team::factory()->for($organization)->create(['name' => 'Ops']);

    Livewire::test(Teams::class, ['organization' => $organization])
        ->call('create')
        ->set('name', 'Ops')
        ->call('save')
        ->assertHasErrors(['name' => 'Ilyen nevű csapat már van a szervezetben.']);
});

it('allows the same team name in another organization', function () {
    actingAsAdmin();
    Team::factory()->create(['name' => 'Ops']);
    $organization = Organization::factory()->create();

    Livewire::test(Teams::class, ['organization' => $organization])
        ->call('create')
        ->set('name', 'Ops')
        ->call('save')
        ->assertHasNoErrors();
});

it('creates a team with users already in the organization and a leader', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $anna = Membership::factory()->for($organization)->create()->user;
    $bela = Membership::factory()->for($organization)->create()->user;
    $foreigner = Membership::factory()->create()->user;

    Livewire::test(Teams::class, ['organization' => $organization])
        ->call('create')
        ->set('name', 'Ops')
        ->set('userIds', [$anna->id, $bela->id, $foreigner->id])
        ->set('leaderUserIds', [$anna->id])
        ->call('save')
        ->assertHasNoErrors();

    $users = $organization->teams()->sole()->users()->get()->keyBy('id');
    expect($users->keys()->sort()->values()->all())->toBe(collect([$anna->id, $bela->id])->sort()->values()->all())
        ->and((bool) $users[$anna->id]->pivot->is_leader)->toBeTrue()
        ->and((bool) $users[$bela->id]->pivot->is_leader)->toBeFalse();
});

it('edits the members of a team', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $team = Team::factory()->for($organization)->create();
    $anna = Membership::factory()->for($organization)->create()->user;
    $bela = Membership::factory()->for($organization)->create()->user;
    $cili = Membership::factory()->for($organization)->create()->user;
    $team->users()->attach([$anna->id => ['is_leader' => true], $bela->id => ['is_leader' => false]]);

    Livewire::test(Teams::class, ['organization' => $organization])
        ->call('edit', $team->id)
        ->assertSet('userIds', [$anna->id, $bela->id])
        ->assertSet('leaderUserIds', [$anna->id])
        ->set('userIds', [$anna->id, $cili->id])
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

    Livewire::test(Teams::class, ['organization' => $organization])
        ->call('create')
        ->assertDontSee($stayed->email)
        ->assertDontSee($outsider->email)
        ->call('edit', $team->id)
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

    Livewire::test(Teams::class, ['organization' => $organization])
        ->call('edit', $team->id)
        ->set('userIds', [$old->id, $new->id])
        ->call('save')
        ->assertHasNoErrors();

    Event::assertDispatchedTimes(TeamMemberAdded::class, 1);
    Event::assertDispatched(TeamMemberAdded::class, fn (TeamMemberAdded $event) => $event->team->is($team) && $event->user->is($new));
});

it('deletes a team while its members stay in the organization', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $team = Team::factory()->for($organization)->create();
    $membership = Membership::factory()->for($organization)->create();
    $membership->user->teams()->attach($team);

    Livewire::test(Teams::class, ['organization' => $organization])->call('delete', $team->id);

    $this->assertModelMissing($team);
    $this->assertModelExists($membership);
    expect($membership->user->teams()->count())->toBe(0);
});

it('has no teams tab for a private person', function () {
    actingAsAdmin();
    $organization = Organization::factory()->individual()->create();

    $response = $this->get(route('admin.organizations.teams', $organization));

    $response->assertNotFound();
});
