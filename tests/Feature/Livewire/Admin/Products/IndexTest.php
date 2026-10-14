<?php

use App\Livewire\Admin\Products\Form;
use App\Livewire\Admin\Products\Index;
use App\Models\Organization;
use App\Models\Product;
use Database\Seeders\ProductSeeder;
use Livewire\Livewire;

it('creates a product with a unique name', function () {
    actingAsAdmin();
    Product::factory()->create(['name' => 'Teljes']);

    Livewire::test(Form::class)->set('name', 'Teljes')->call('save')->assertHasErrors(['name' => 'unique']);
    Livewire::test(Form::class)->set('name', 'Snapshot · Belső')->call('save')->assertHasNoErrors();

    expect(Product::query()->where('name', 'Snapshot · Belső')->exists())->toBeTrue();
});

it('deletes a product without organizations', function () {
    actingAsAdmin();
    $product = Product::factory()->create();

    Livewire::test(Index::class)->call('delete', $product->id);

    $this->assertModelMissing($product);
});

it('keeps a product that organizations use', function () {
    actingAsAdmin();
    $product = Product::factory()->create();
    Organization::factory()->for($product)->create();

    $component = Livewire::test(Index::class)->call('delete', $product->id);

    $this->assertModelExists($product);
    $component->assertSee('Csak olyan termék törölhető, amelyhez nem tartozik szervezet.');
});

it('seeds the three starting products once', function () {
    $this->seed(ProductSeeder::class);
    $this->seed(ProductSeeder::class);

    expect(Product::query()->orderBy('id')->pluck('name')->all())->toBe(['Snapshot · Külső', 'Snapshot · Belső', 'Teljes']);
});
