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
