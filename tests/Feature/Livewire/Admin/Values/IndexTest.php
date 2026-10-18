<?php

use App\Enums\ValueGrade;
use App\Enums\ValueOrigin;
use App\Enums\ValueSvsStatus;
use App\Livewire\Admin\Values\Index;
use App\Models\Organization;
use App\Models\Value;
use App\Models\ValueCategory;
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

it('finds a value by its English name and by its code', function () {
    actingAsAdmin();
    Value::factory()->create(['name' => 'Kíváncsiság', 'name_en' => 'Curious', 'code' => '1']);
    Value::factory()->create(['name' => 'Játékosság', 'code' => '12']);

    Livewire::test(Index::class)
        ->set('search', 'curious')
        ->assertSee('Kíváncsiság')
        ->assertDontSee('Játékosság')
        ->set('search', '12')
        ->assertSee('Játékosság')
        ->assertDontSee('Kíváncsiság');
});

it('filters by higher order value, literature support and SVS status', function () {
    actingAsAdmin();
    $openness = ValueCategory::factory()->create(['higher_order' => 'Nyitottság a változásra (Openness to change)']);
    $conservation = ValueCategory::factory()->create(['higher_order' => 'Megőrzés (Conservation)']);
    Value::factory()->for($openness, 'category')->create(['name' => 'Kíváncsiság', 'evidence' => ValueGrade::Strong, 'svs_status' => ValueSvsStatus::Yes]);
    Value::factory()->for($conservation, 'category')->create(['name' => 'Engedelmes', 'evidence' => ValueGrade::Weak, 'svs_status' => ValueSvsStatus::XCoded]);

    Livewire::test(Index::class)
        ->set('filters.higher_order', 'Megőrzés (Conservation)')
        ->assertSee('Engedelmes')
        ->assertDontSee('Kíváncsiság')
        ->set('filters.higher_order', '')
        ->set('filters.evidence', 'strong')
        ->assertSee('Kíváncsiság')
        ->assertDontSee('Engedelmes')
        ->set('filters.evidence', '')
        ->set('filters.svs', 'x_coded')
        ->assertSee('Engedelmes')
        ->assertDontSee('Kíváncsiság');
});
