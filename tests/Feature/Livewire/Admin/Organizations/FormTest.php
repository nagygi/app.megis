<?php

use App\Enums\OrganizationType;
use App\Livewire\Admin\Organizations\Form;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Product;
use Livewire\Livewire;

it('creates an organization with a slug made from its name', function () {
    actingAsAdmin();
    $product = Product::factory()->create();

    $component = Livewire::test(Form::class)
        ->set('name', 'Árvíztűrő Tükörfúró Kft.')
        ->set('product_id', $product->id)
        ->set('headcount_limit', 10);

    $component->assertSet('slug', 'arvizturo-tukorfuro-kft');
    $component->call('save')->assertHasNoErrors();

    $organization = Organization::query()->sole();
    expect($organization)
        ->slug->toBe('arvizturo-tukorfuro-kft')
        ->type->toBe(OrganizationType::Organization)
        ->headcount_limit->toBe(10);
    $component->assertRedirect(route('admin.organizations.members', $organization));
});

it('rejects a slug that is a system path', function () {
    actingAsAdmin();

    Livewire::test(Form::class)
        ->set('name', 'Admin')
        ->call('unlockSlug')
        ->set('slug', 'admin')
        ->set('product_id', Product::factory()->create()->id)
        ->call('save')
        ->assertHasErrors(['slug' => 'Ez a cím foglalt. Válassz másikat.']);
});

it('rejects a slug another organization uses', function () {
    actingAsAdmin();
    Organization::factory()->create(['slug' => 'acme']);

    Livewire::test(Form::class)
        ->set('name', 'Acme')
        ->call('unlockSlug')
        ->set('slug', 'acme')
        ->set('product_id', Product::factory()->create()->id)
        ->call('save')
        ->assertHasErrors(['slug' => 'unique']);
});

it('requires a name and a product', function () {
    actingAsAdmin();

    Livewire::test(Form::class)
        ->call('save')
        ->assertHasErrors(['name' => 'required', 'product_id' => 'required']);
});

it('does not let the limit go below the current headcount', function () {
    actingAsAdmin();
    $organization = Organization::factory()->withHeadcountLimit(5)->create();
    Membership::factory()->count(3)->for($organization)->create();

    Livewire::test(Form::class, ['organization' => $organization])
        ->set('headcount_limit', 2)
        ->call('save')
        ->assertHasErrors(['headcount_limit' => 'A limit nem lehet kisebb a jelenlegi létszámnál (3).']);
});

it('stores no limit for a private person', function () {
    actingAsAdmin();

    Livewire::test(Form::class)
        ->set('type', OrganizationType::Individual->value)
        ->set('name', 'Kiss Péter')
        ->set('product_id', Product::factory()->create()->id)
        ->set('headcount_limit', 7)
        ->call('save')
        ->assertHasNoErrors();

    expect(Organization::query()->sole())
        ->type->toBe(OrganizationType::Individual)
        ->headcount_limit->toBeNull()
        ->effectiveHeadcountLimit()->toBe(1);
});

it('keeps the type when an organization is edited', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();

    Livewire::test(Form::class, ['organization' => $organization])
        ->set('type', OrganizationType::Individual->value)
        ->set('name', 'Új név')
        ->call('save')
        ->assertHasNoErrors();

    expect($organization->fresh())
        ->name->toBe('Új név')
        ->type->toBe(OrganizationType::Organization);
});

it('warns that old links stop working when the slug changes', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create(['slug' => 'regi-cim']);

    Livewire::test(Form::class, ['organization' => $organization])
        ->call('unlockSlug')
        ->set('slug', 'uj-cim')
        ->assertSee('A régi linkek nem működnek tovább.');
});
