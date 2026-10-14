<?php

use App\Enums\ValueOrigin;
use App\Livewire\Admin\Organizations\Values;
use App\Models\Organization;
use App\Models\OrganizationValueSet;
use App\Models\Value;
use App\Services\Values\ValueService;
use Livewire\Livewire;

function libraryValue(string $name): Value
{
    return app(ValueService::class)->create([
        'name' => $name,
        'meanings' => [$name.': első jelentés', $name.': második jelentés'],
        'high_examples' => [$name.' nagyon'],
        'low_examples' => [$name.' egyáltalán nem'],
    ]);
}

function threeValueSet(mixed $component): mixed
{
    foreach (['Bátorság', 'Nyíltság', 'Felelősség'] as $name) {
        $component->set('pickedValueId', libraryValue($name)->id)->call('addFromLibrary');
    }

    return $component;
}

it('prefills a value from the Értéktár with the first item of each list', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $value = libraryValue('Bátorság');

    $component = Livewire::test(Values::class, ['organization' => $organization])
        ->set('pickedValueId', $value->id)
        ->call('addFromLibrary');

    expect($component->get('items')[0])->toMatchArray([
        'value_id' => $value->id,
        'source_version_id' => $value->latestVersion->id,
        'meaning' => 'Bátorság: első jelentés',
        'high_example' => 'Bátorság nagyon',
        'low_example' => 'Bátorság egyáltalán nem',
    ]);
});

it('requires three to five values with both scale ends', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();

    Livewire::test(Values::class, ['organization' => $organization])
        ->set('pickedValueId', libraryValue('Kíváncsiság')->id)
        ->call('addFromLibrary')
        ->call('save')
        ->assertHasErrors(['items' => 'min']);

    threeValueSet(Livewire::test(Values::class, ['organization' => $organization]))
        ->set('items.0.low_example', '')
        ->call('save')
        ->assertHasErrors(['items.0.low_example' => 'required']);

    expect(OrganizationValueSet::query()->count())->toBe(0);
});

it('stops adding at five values', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $component = threeValueSet(Livewire::test(Values::class, ['organization' => $organization]));

    foreach (['Kíváncsiság', 'Gondoskodás', 'Játékosság'] as $name) {
        $component->set('pickedValueId', libraryValue($name)->id)->call('addFromLibrary');
    }

    expect($component->get('items'))->toHaveCount(5);
});

it('saves the set as a version and keeps the Értéktár value as it was', function () {
    $admin = actingAsAdmin();
    $organization = Organization::factory()->create();

    threeValueSet(Livewire::test(Values::class, ['organization' => $organization]))
        ->set('items.0.meaning', 'Nálunk ez azt jelenti, hogy kimondjuk.')
        ->set('purpose', 'Hogy a csapat merjen.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Mentve, első verzió: v1.');

    $set = $organization->valueSet()->sole();
    expect($set->items()->count())->toBe(3)
        ->and($set->purpose)->toBe('Hogy a csapat merjen.')
        ->and($set->latestVersion)->number->toBe(1)->created_by->toBe($admin->id)
        ->and($set->latestVersion->snapshot['values'][0]['meaning'])->toBe('Nálunk ez azt jelenti, hogy kimondjuk.')
        ->and(Value::query()->firstWhere('name', 'Bátorság')->meanings[0])->toBe('Bátorság: első jelentés');
});

it('warns that new diagnoses use the new values after a change', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    threeValueSet(Livewire::test(Values::class, ['organization' => $organization]))->call('save');

    Livewire::test(Values::class, ['organization' => $organization])
        ->assertSee('A korábbi értékek nem törlődnek. Az új diagnózisok az új értékek szerint futnak.')
        ->call('save')
        ->assertSee('Nem változott semmi, nem készült új verzió.')
        ->set('impact', 'Bátrabb csapatok.')
        ->call('save')
        ->assertSee('Mentve, új verzió: v2.');

    expect($organization->valueSet->versions()->count())->toBe(2);
});

it('adds a new value to the Értéktár as found by the organization', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();

    $component = threeValueSet(Livewire::test(Values::class, ['organization' => $organization]))
        ->set('newValueName', 'Lendület')
        ->call('addNew')
        ->set('items.3.low_example', 'Megállunk az első akadálynál.')
        ->set('items.3.high_example', 'Az akadály is lendületet ad.')
        ->call('save')
        ->assertHasNoErrors();

    $value = Value::query()->firstWhere('name', 'Lendület');
    expect($value)
        ->origin->toBe(ValueOrigin::Organization)
        ->origin_organization_id->toBe($organization->id)
        ->reviewed_at->toBeNull()
        ->low_examples->toBe(['Megállunk az első akadálynál.'])
        ->and($component->get('items')[3]['value_id'])->toBe($value->id);
});

it('does not take a name that is already in the Értéktár as a new value', function () {
    actingAsAdmin();
    libraryValue('Bátorság');

    Livewire::test(Values::class, ['organization' => Organization::factory()->create()])
        ->set('newValueName', 'Bátorság')
        ->call('addNew')
        ->assertHasErrors(['newValueName']);
});

it('offers only values the organization may choose', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    libraryValue('Átnézett');
    Value::factory()->from(ValueOrigin::Organization, Organization::factory()->create())->create(['name' => 'Másik szervezeté']);
    Value::factory()->archived()->create(['name' => 'Archivált érték']);

    Livewire::test(Values::class, ['organization' => $organization])
        ->assertSee('Átnézett')
        ->assertDontSee('Másik szervezeté')
        ->assertDontSee('Archivált érték');
});

it('opens a saved version read-only', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    threeValueSet(Livewire::test(Values::class, ['organization' => $organization]))->set('purpose', 'Első cél.')->call('save');

    Livewire::test(Values::class, ['organization' => $organization])
        ->set('purpose', 'Második cél.')
        ->call('save')
        ->set('viewing', 1)
        ->assertSee('v1 · csak olvasható')
        ->assertSee('Első cél.');
});
