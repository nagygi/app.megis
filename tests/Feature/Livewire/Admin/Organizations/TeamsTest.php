<?php

use App\Livewire\Admin\Organizations\Teams;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Team;
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
