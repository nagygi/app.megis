<?php

use App\Enums\ValueOrigin;
use App\Livewire\Admin\Values\Index;
use App\Models\Organization;
use App\Models\Value;
use Livewire\Livewire;

it('hides archived values unless asked for', function () {
    actingAsAdmin();
    Value::factory()->create(['name' => 'Bátorság']);
    Value::factory()->archived()->create(['name' => 'Kivezetett érték']);

    Livewire::test(Index::class)
        ->assertSee('Bátorság')
        ->assertDontSee('Kivezetett érték')
        ->set('filters.status', 'archived')
        ->assertSee('Kivezetett érték')
        ->assertDontSee('Bátorság')
        ->set('filters.status', 'all')
        ->assertSee('Kivezetett érték')
        ->assertSee('Bátorság');
});

it('finds a value by one of its synonyms', function () {
    actingAsAdmin();
    Value::factory()->create(['name' => 'Egyenesség', 'keywords' => ['őszinteség', 'kimondás']]);
    Value::factory()->create(['name' => 'Játékosság']);

    Livewire::test(Index::class)
        ->set('search', 'KIMOND')
        ->assertSee('Egyenesség')
        ->assertDontSee('Játékosság');
});

it('lists the values waiting for review with their origin', function () {
    actingAsAdmin();
    Value::factory()->create(['name' => 'Átnézett érték']);
    Value::factory()->from(ValueOrigin::Diagnosis, Organization::factory()->create())->create(['name' => 'Workshopon felvett']);

    Livewire::test(Index::class)
        ->set('filters.review', 'awaiting')
        ->assertSee('Workshopon felvett')
        ->assertSee('Diagnózisnál felvett')
        ->assertDontSee('Átnézett érték');
});

it('sorts by version number', function () {
    actingAsAdmin();
    $older = Value::factory()->create(['name' => 'Alfa', 'meanings' => ['a1']]);
    $older->recordVersion();
    $newer = Value::factory()->create(['name' => 'Zeta', 'meanings' => ['b1']]);
    $newer->recordVersion();
    $newer->update(['meanings' => ['b2']]);
    $newer->recordVersion();

    Livewire::test(Index::class)
        ->call('sortBy', 'version')
        ->assertSeeInOrder(['Alfa', 'Zeta'])
        ->call('sortBy', 'version')
        ->assertSeeInOrder(['Zeta', 'Alfa']);
});
