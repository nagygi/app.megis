<?php

use App\Enums\ValueOrigin;
use App\Models\Organization;
use App\Models\Value;
use Illuminate\Support\Arr;

it('offers reviewed values everywhere and unreviewed ones only where they were created', function () {
    $home = Organization::factory()->create();
    $other = Organization::factory()->create();
    $reviewed = Value::factory()->create();
    $fromHome = Value::factory()->from(ValueOrigin::Organization, $home)->create();
    Value::factory()->archived()->create();

    expect(Value::query()->availableTo($home)->pluck('id')->all())->toEqualCanonicalizing([$reviewed->id, $fromHome->id])
        ->and(Value::query()->availableTo($other)->pluck('id')->all())->toBe([$reviewed->id])
        ->and(Value::query()->availableTo(null)->pluck('id')->all())->toBe([$reviewed->id]);
});

it('knows whether both ends of the scale are filled', function () {
    expect(Value::factory()->create()->has_endpoints)->toBeTrue()
        ->and(Value::factory()->withoutEndpoints()->create()->has_endpoints)->toBeFalse();
});

it('does not record a version when the content is the same', function () {
    $value = Value::factory()->create();

    expect($value->recordVersion())->not->toBeNull()
        ->and($value->recordVersion())->toBeNull();
});

it('does not record a version for an unchanged value whose version was taken before the card had more fields', function () {
    $value = Value::factory()->create();
    $version = $value->recordVersion();
    $version->forceFill(['snapshot' => Arr::except($version->snapshot, [
        'code', 'name_en', 'definition', 'definition_en', 'false_positives', 'overlaps', 'literature', 'evidence', 'transposability', 'svs_status',
    ])])->save();

    expect($value->recordVersion())->toBeNull();

    $value->update(['false_positives' => ['Hangoskodás.']]);

    expect($value->recordVersion()->changed_fields)->toBe(['false_positives']);
});

it('is found by its English name as well', function () {
    $value = Value::factory()->create(['name' => 'Kíváncsiság', 'name_en' => 'Curious', 'keywords' => ['exploráció']]);

    expect($value->search_text)->toBe("kíváncsiság\ncurious\nexploráció");
});
