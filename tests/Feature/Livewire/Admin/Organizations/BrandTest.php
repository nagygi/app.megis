<?php

use App\Livewire\Admin\Organizations\Brand;
use App\Models\Organization;
use App\Models\OrganizationValueSet;
use App\Models\Value;
use App\Services\Organizations\OrganizationValueSetService;
use Livewire\Livewire;

function organizationWithValues(): Organization
{
    $organization = Organization::factory()->create();
    $valueSets = app(OrganizationValueSetService::class);
    $values = collect(['Bátorság', 'Nyíltság', 'Felelősség'])->map(fn (string $name) => Value::factory()->create(['name' => $name]));

    $valueSets->save($valueSets->forOrganization($organization), $values->map(fn (Value $value) => $valueSets->itemFromValue($value->load('latestVersion')))->all(), []);

    return $organization;
}

function completeBrand(mixed $component): mixed
{
    return $component
        ->set('purpose', 'Hogy a csapat merjen.')
        ->set('value_proposition', 'Egy bátrabb csapatot.')
        ->set('impact', 'Kimondott mondatok.');
}

it('requires all of the brand base, as on the customer side', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();

    Livewire::test(Brand::class, ['organization' => $organization])
        ->set('purpose', '')
        ->set('value_proposition', '')
        ->set('impact', ' ')
        ->call('save')
        ->assertHasErrors(['purpose' => 'required', 'value_proposition' => 'required', 'impact' => 'required']);

    expect(OrganizationValueSet::query()->count())->toBe(0);
});

it('lists the saved values as the root', function () {
    actingAsAdmin();
    $organization = organizationWithValues();

    Livewire::test(Brand::class, ['organization' => $organization])
        ->assertSee('Bátorság · Nyíltság · Felelősség')
        ->assertSee(route('admin.organizations.values', $organization));
});

it('saves the brand base as a new version and keeps the values', function () {
    $admin = actingAsAdmin();
    $organization = organizationWithValues();
    $set = $organization->valueSet()->sole();
    $keys = $set->items()->pluck('key')->all();

    completeBrand(Livewire::test(Brand::class, ['organization' => $organization]))
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Mentve, új verzió: v2.');

    $set->refresh();
    expect($set->purpose)->toBe('Hogy a csapat merjen.')
        ->and($set->items()->pluck('key')->all())->toBe($keys)
        ->and($set->latestVersion)->number->toBe(2)->created_by->toBe($admin->id)
        ->and($set->latestVersion->snapshot['impact'])->toBe('Kimondott mondatok.')
        ->and(collect($set->latestVersion->snapshot['values'])->pluck('name')->all())->toBe(['Bátorság', 'Nyíltság', 'Felelősség']);

    Livewire::test(Brand::class, ['organization' => $organization])
        ->assertSet('purpose', 'Hogy a csapat merjen.')
        ->call('save')
        ->assertSee('Nem változott semmi, nem készült új verzió.');
});

it('opens a saved version read-only', function () {
    actingAsAdmin();
    $organization = organizationWithValues();
    completeBrand(Livewire::test(Brand::class, ['organization' => $organization]))->set('purpose', 'Első cél.')->call('save');

    Livewire::test(Brand::class, ['organization' => $organization])
        ->set('purpose', 'Második cél.')
        ->call('save')
        ->set('viewing', 2)
        ->assertSee('v2 · csak olvasható')
        ->assertSee('Első cél.')
        ->assertDontSee('Második cél.')
        ->assertSee('Bátorság · Nyíltság · Felelősség');
});
