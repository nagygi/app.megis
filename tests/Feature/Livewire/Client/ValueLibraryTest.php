<?php

use App\Enums\ValueOrigin;
use App\Livewire\Client\ValueLibrary;
use App\Models\Organization;
use App\Models\Value;
use App\Models\ValueCategory;
use App\Services\Organizations\OrganizationValueSetService;
use Livewire\Livewire;

it('shows the organization’s values in its own words', function () {
    $organization = Organization::factory()->create(['name' => 'Acme Kft.']);
    actingAsCustomer(customerOf($organization));
    $value = Value::factory()->create(['name' => 'Bátorság']);
    $value->recordVersion();
    $service = app(OrganizationValueSetService::class);
    $set = $service->forOrganization($organization);
    $item = [...$service->itemFromValue($value->fresh('latestVersion')), 'meaning' => 'Nálunk kimondjuk.'];
    $service->save($set, [$item], ['purpose' => null]);

    Livewire::test(ValueLibrary::class, ['organization' => $organization])
        ->assertSee('A szervezet értékei')
        ->assertSee('Nálunk kimondjuk.');
});

it('leaves the organization block out without a value set', function () {
    $organization = Organization::factory()->create();
    actingAsCustomer(customerOf($organization));

    Livewire::test(ValueLibrary::class, ['organization' => $organization])->assertDontSee('A szervezet értékei');
});

it('browses only active, reviewed Értéktár values by name, synonym and category', function () {
    $organization = Organization::factory()->create();
    actingAsCustomer(customerOf($organization));
    $category = ValueCategory::factory()->create();
    Value::factory()->for($category, 'category')->create(['name' => 'Egyenesség', 'keywords' => ['őszinteség']]);
    Value::factory()->create(['name' => 'Játékosság']);
    Value::factory()->archived()->create(['name' => 'Archivált']);
    Value::factory()->from(ValueOrigin::Ai, $organization)->create(['name' => 'Még nem átnézett']);

    Livewire::test(ValueLibrary::class, ['organization' => $organization])
        ->assertSee('Egyenesség')
        ->assertSee('Játékosság')
        ->assertDontSee('Archivált')
        ->assertDontSee('Még nem átnézett')
        ->set('search', 'ŐSZINTE')
        ->assertSee('Egyenesség')
        ->assertDontSee('Játékosság')
        ->set('search', '')
        ->set('category', (string) $category->id)
        ->assertDontSee('Játékosság');
});

it('opens from the menu', function () {
    $organization = Organization::factory()->create();
    actingAsCustomer(customerOf($organization));

    $this->get(route('client.home', $organization))
        ->assertSee(route('client.values', $organization))
        ->assertSee(route('client.mirror.record', $organization));
});
