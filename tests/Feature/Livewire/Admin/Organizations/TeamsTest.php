<?php

use App\Livewire\Admin\Organizations\Teams;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Team;
use Livewire\Livewire;

it('lists the teams with links to their own form, not the form itself', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $team = Team::factory()->for($organization)->create(['name' => 'Ops']);

    Livewire::test(Teams::class, ['organization' => $organization])
        ->assertSee('Ops')
        ->assertSee(route('admin.organizations.teams.create', $organization))
        ->assertSee(route('admin.organizations.teams.edit', [$organization, $team]))
        ->assertDontSee('wire:submit="save"', false);
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
