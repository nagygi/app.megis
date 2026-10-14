<?php

use App\Enums\ValueOrigin;
use App\Enums\VersionEventType;
use App\Livewire\Admin\Values\Form;
use App\Models\Organization;
use App\Models\Value;
use App\Models\ValueCategory;
use Livewire\Livewire;

function fillValueForm(mixed $component, string $name = 'Kíváncsiság'): mixed
{
    return $component
        ->set('name', $name)
        ->set('meanings', ['Kérdezek, mielőtt ítélek.'])
        ->set('high_examples', ['A kérdés az első mondat.'])
        ->set('low_examples', ['Már el is döntöttem, mire megkérdezem.']);
}

it('creates a reviewed value as v1 with the slug made from the name', function () {
    $admin = actingAsAdmin();
    $category = ValueCategory::factory()->create();

    fillValueForm(Livewire::test(Form::class))
        ->set('value_category_id', $category->id)
        ->call('save')
        ->assertHasNoErrors();

    $value = Value::query()->sole();
    expect($value)
        ->slug->toBe('kivancsisag')
        ->origin->toBe(ValueOrigin::Admin)
        ->reviewed_at->not->toBeNull()
        ->has_endpoints->toBeTrue()
        ->and($value->latestVersion)
        ->number->toBe(1)
        ->created_by->toBe($admin->id)
        ->and($value->latestVersion->snapshot['category'])->toBe(['id' => $category->id, 'name' => $category->name]);
});

it('requires both ends of the scale', function () {
    actingAsAdmin();

    fillValueForm(Livewire::test(Form::class))
        ->set('high_examples', [''])
        ->set('low_examples', [])
        ->call('save')
        ->assertHasErrors(['high_examples' => 'min', 'low_examples' => 'min']);

    expect(Value::query()->count())->toBe(0);
});

it('requires the ends when a value from a diagnosis is first edited', function () {
    actingAsAdmin();
    $value = Value::factory()->from(ValueOrigin::Diagnosis, Organization::factory()->create())->withoutEndpoints()->create();

    Livewire::test(Form::class, ['value' => $value])
        ->call('save')
        ->assertHasErrors(['high_examples', 'low_examples']);
});

it('rejects a name that is already in the Értéktár, whatever the case', function () {
    actingAsAdmin();
    Value::factory()->create(['name' => 'Nyitottság']);

    fillValueForm(Livewire::test(Form::class), 'nyitottság')
        ->call('save')
        ->assertHasErrors(['name']);
});

it('drops empty rows and rejects items shorter than two characters', function () {
    actingAsAdmin();

    $component = fillValueForm(Livewire::test(Form::class))
        ->set('examples', ['', 'x', '  '])
        ->call('save')
        ->assertHasErrors(['examples.0' => 'min']);

    expect($component->get('examples'))->toBe(['x']);
});

it('records a new version only when something changed', function () {
    actingAsAdmin();
    $value = Value::factory()->create(['meanings' => ['Kérdezek.']]);
    $value->recordVersion();

    Livewire::test(Form::class, ['value' => $value])
        ->call('save')
        ->assertSee('Nem változott semmi, nem készült új verzió.');

    Livewire::test(Form::class, ['value' => $value->fresh()])
        ->set('meanings', ['Kérdezek.', 'Meghallgatom a választ.'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Mentve, új verzió: v2.');

    $latest = $value->fresh()->latestVersion;
    expect($latest->number)->toBe(2)
        ->and($latest->changed_fields)->toBe(['meanings'])
        ->and($value->versions()->count())->toBe(2);
});

it('keeps the order set by dragging or by the arrow buttons', function () {
    actingAsAdmin();
    $value = Value::factory()->create(['meanings' => ['Első', 'Második', 'Harmadik']]);

    Livewire::test(Form::class, ['value' => $value])
        ->call('sortListItem', 'meanings', 2, 0)
        ->assertSet('meanings', ['Harmadik', 'Első', 'Második'])
        ->call('moveListItem', 'meanings', 1, 1)
        ->assertSet('meanings', ['Harmadik', 'Második', 'Első'])
        ->call('save');

    expect($value->fresh()->meanings)->toBe(['Harmadik', 'Második', 'Első']);
});

it('refuses to edit a property that is not a list', function () {
    actingAsAdmin();

    Livewire::test(Form::class)->call('addListItem', 'name')->assertNotFound();
});

it('logs archiving, restoring and reviewing as events, not versions', function () {
    actingAsAdmin();
    $value = Value::factory()->from(ValueOrigin::Ai)->create();
    $value->recordVersion();

    Livewire::test(Form::class, ['value' => $value])
        ->call('markReviewed')
        ->call('archive')
        ->call('restore');

    $value->refresh();
    expect($value->isReviewed())->toBeTrue()
        ->and($value->isArchived())->toBeFalse()
        ->and($value->versions()->count())->toBe(1)
        ->and($value->versionEvents()->pluck('type')->all())->toBe([
            VersionEventType::Reviewed, VersionEventType::Archived, VersionEventType::Restored,
        ]);
});

it('is not available to customers', function () {
    actingAsCustomer(customerOf());

    $this->get(route('admin.values.create'))->assertForbidden();
});
