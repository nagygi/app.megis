<?php

use App\Livewire\Admin\Settings\Areas;
use App\Models\Area;
use Livewire\Livewire;

it('lists the five areas and edits their texts (18 · 18.1)', function () {
    actingAsAdmin();
    $area = Area::query()->where('key', 'strategy')->sole();

    Livewire::test(Areas::class)
        ->assertSee('Stratégia')
        ->assertSee('Növekedés és tanulás')
        ->call('edit', $area->id)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name'])
        ->set('name', 'Stratégia')
        ->set('whatWeLookAt', 'Tudják-e, merre mennek.')
        ->set('trace', 'A döntések indoklása a jegyzetekben.')
        ->set('sixDescription', 'Mindenki ugyanazt mondja az irányról.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Tudják-e, merre mennek.');

    expect($area->refresh()->trace)->toBe('A döntések indoklása a jegyzetekben.')
        ->and(Area::query()->count())->toBe(5);
});

it('keeps the areas from customers', function () {
    actingAsCustomer(customerOf());

    $this->get(route('admin.settings.areas'))->assertForbidden();
});
