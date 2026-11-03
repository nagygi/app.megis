<?php

use App\Livewire\Admin\Organizations\Index;
use App\Models\Organization;
use Livewire\Livewire;

it('shows when the organization was added and when a member last worked in it', function () {
    actingAsAdmin();
    Organization::factory()->create(['name' => 'Alfa Kft.', 'created_at' => '2026-10-14 09:00:00', 'last_activity_at' => '2026-11-02 16:45:00']);
    Organization::factory()->create(['name' => 'Zeta Kft.']);

    $component = Livewire::test(Index::class);

    $component
        ->assertSeeInOrder(['Felvitel ideje', 'Utolsó aktivitás'])
        ->assertSeeInOrder(['Alfa Kft.', '2026.10.14.', '2026.11.02. 16:45', 'Zeta Kft.', 'még nem']);
});

it('sorts by the last activity', function () {
    actingAsAdmin();
    Organization::factory()->create(['name' => 'Alfa Kft.', 'last_activity_at' => now()->subDay()]);
    Organization::factory()->create(['name' => 'Zeta Kft.', 'last_activity_at' => now()->subDays(3)]);

    Livewire::test(Index::class)
        ->call('sortBy', 'last_activity')
        ->assertSeeInOrder(['Zeta Kft.', 'Alfa Kft.'])
        ->call('sortBy', 'last_activity')
        ->assertSeeInOrder(['Alfa Kft.', 'Zeta Kft.']);
});
