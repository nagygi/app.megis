<?php

use App\Enums\ValueGrade;
use App\Livewire\Admin\Values\Versions;
use App\Models\Value;
use App\Services\Values\ValueService;
use Livewire\Livewire;

function valueWithTwoVersions(): Value
{
    $value = app(ValueService::class)->create([
        'name' => 'Nyíltság',
        'meanings' => ['Kimondom, amit gondolok.', 'Megosztom a rossz hírt is.'],
        'high_examples' => ['A rossz hír aznap kimegy.'],
        'low_examples' => ['A rossz hír megvárja, amíg biztos.'],
    ]);

    app(ValueService::class)->update($value, [
        'meanings' => ['Megosztom a rossz hírt is.', 'Kimondom, amit gondolok.', 'Kérdezek, ha nem értem.'],
        'low_examples' => ['A rossz hír hetekig vár.'],
    ]);

    return $value->fresh();
}

it('lists the versions with the fields that changed', function () {
    actingAsAdmin();
    $value = valueWithTwoVersions();

    Livewire::test(Versions::class, ['value' => $value])
        ->assertSeeInOrder(['v2', 'Mit jelent?, Mikor nagyon nem teljesül?', 'v1', 'első verzió']);
});

it('opens an old version read-only', function () {
    actingAsAdmin();
    $value = valueWithTwoVersions();

    Livewire::test(Versions::class, ['value' => $value])
        ->set('viewing', 1)
        ->assertSee('v1 · csak olvasható')
        ->assertSee('A rossz hír megvárja, amíg biztos.')
        ->assertDontSee('A rossz hír hetekig vár.');
});

it('compares two versions side by side', function () {
    actingAsAdmin();
    $value = valueWithTwoVersions();

    Livewire::test(Versions::class, ['value' => $value])
        ->set('left', 2)
        ->set('right', 1)
        ->call('compare')
        ->assertSet('left', 1)
        ->assertSet('right', 2)
        ->assertSee('v1 és v2 összehasonlítása')
        ->assertSeeHtml('<span class="sr-only">hozzáadva:</span>')
        ->assertSeeHtml('<span class="sr-only">áthelyezve:</span>')
        ->assertSee('Kérdezek, ha nem értem.');
});

it('shows archiving in the history', function () {
    actingAsAdmin();
    $value = valueWithTwoVersions();
    app(ValueService::class)->archive($value);

    Livewire::test(Versions::class, ['value' => $value])->assertSee('Archiválva');
});

it('compares the definition and the ratings by their labels', function () {
    actingAsAdmin();
    $values = app(ValueService::class);
    $value = $values->create([
        'name' => 'Kíváncsiság',
        'definition' => 'Érdeklődés mindenre.',
        'high_examples' => ['A kérdés az első mondat.'],
        'low_examples' => ['Már eldöntöttem.'],
        'evidence' => ValueGrade::Strong,
    ]);
    $values->update($value, ['definition' => 'A megismerés önmagáért.', 'evidence' => ValueGrade::Medium]);

    Livewire::test(Versions::class, ['value' => $value->fresh()])
        ->assertSee('Definíció, Szakirodalmi alátámasztottság')
        ->set('left', 1)
        ->set('right', 2)
        ->call('compare')
        ->assertSee('mindenre.')
        ->assertSee('önmagáért.')
        ->assertSee('erős')
        ->assertSee('közepes');
});
