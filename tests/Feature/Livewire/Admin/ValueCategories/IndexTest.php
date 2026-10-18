<?php

use App\Livewire\Admin\ValueCategories\Form;
use App\Livewire\Admin\ValueCategories\Index;
use App\Models\Value;
use App\Models\ValueCategory;
use Livewire\Livewire;

it('creates a category with a unique name', function () {
    actingAsAdmin();
    ValueCategory::factory()->create(['name' => 'Nyitottság a változásra']);

    Livewire::test(Form::class)->set('name', 'Nyitottság a változásra')->call('save')->assertHasErrors(['name' => 'unique']);
    Livewire::test(Form::class)->set('name', 'Önmeghaladás')->call('save')->assertHasNoErrors();

    expect(ValueCategory::query()->where('name', 'Önmeghaladás')->exists())->toBeTrue();
});

it('deletes a category without values', function () {
    actingAsAdmin();
    $category = ValueCategory::factory()->create();

    Livewire::test(Index::class)->call('delete', $category->id);

    $this->assertModelMissing($category);
});

it('keeps a category that has values', function () {
    actingAsAdmin();
    $category = ValueCategory::factory()->create();
    Value::factory()->for($category, 'category')->create();

    Livewire::test(Index::class)
        ->call('delete', $category->id)
        ->assertSee('Ehhez a kategóriához tartozik érték, ezért nem törölhető.');

    $this->assertModelExists($category);
});

it('saves the English name and the higher order value, and lists them', function () {
    actingAsAdmin();

    Livewire::test(Form::class)
        ->set('name', 'Önállóság')
        ->set('name_en', 'Self-direction')
        ->set('higher_order', 'Nyitottság a változásra (Openness to change)')
        ->call('save')
        ->assertHasNoErrors();

    expect(ValueCategory::query()->sole())
        ->name_en->toBe('Self-direction')
        ->higher_order->toBe('Nyitottság a változásra (Openness to change)');

    Livewire::test(Index::class)
        ->assertSee('Önállóság (Self-direction)')
        ->assertSee('Nyitottság a változásra (Openness to change)');
});
