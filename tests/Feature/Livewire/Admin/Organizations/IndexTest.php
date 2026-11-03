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

it('lists the most recently active first and keeps the never active at the end either way', function () {
    actingAsAdmin();
    Organization::factory()->create(['name' => 'Alfa Kft.']);
    Organization::factory()->create(['name' => 'Beta Kft.', 'last_activity_at' => now()->subDays(3)]);
    Organization::factory()->create(['name' => 'Gamma Kft.', 'last_activity_at' => now()->subDay()]);

    Livewire::test(Index::class)
        ->assertSeeInOrder(['Gamma Kft.', 'Beta Kft.', 'Alfa Kft.'])
        ->call('sortBy', 'last_activity')
        ->assertSeeInOrder(['Beta Kft.', 'Gamma Kft.', 'Alfa Kft.']);
});
