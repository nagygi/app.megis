<?php

use App\Enums\ValueOrigin;
use App\Models\Organization;
use App\Models\Value;

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
