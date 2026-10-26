<?php

use App\Enums\MirrorStatus;
use App\Livewire\Admin\Mirrors\Show;
use App\Models\MirrorSession;
use App\Models\User;
use App\Models\Value;
use Livewire\Livewire;

it('shows the values of a mirror waiting for the e-mail confirmation, with their quotes', function () {
    actingAsAdmin();
    $value = Value::factory()->create(['name' => 'Egyenesség']);
    $mirror = MirrorSession::factory()->create(['email' => 'anna@example.com', 'confirmation_sent_at' => now(), 'accepted_at' => now()]);
    $kept = $mirror->material->detections()->create(['speaker' => 'Beszélő 1', 'quote' => 'inkább most mondom', 'free_name' => 'Egyenesség', 'reasoning' => 'R']);
    $kept->matches()->create(['value_id' => $value->id, 'confidence' => 0.9]);
    $mirror->material->detections()->create(['speaker' => 'Beszélő 1', 'quote' => 'nevettünk egyet', 'free_name' => 'Humor', 'reasoning' => 'R', 'excluded_at' => now()]);
    $mirror->insights()->create(['group_key' => 'value:'.$value->id, 'meaning' => 'Kimondom, amit gondolok.', 'signs' => [], 'accepted_at' => now()]);

    $this->get(route('admin.mirrors.show', $mirror))
        ->assertOk()
        ->assertSeeInOrder(['Megerősítésre vár', 'anna@example.com', 'A megerősítő levél elment'])
        ->assertSeeInOrder(['Egyenesség', 'Kimondom, amit gondolok.', 'elfogadta', 'inkább most mondom'])
        // A value marked "nem az enyém" keeps its place, as on ET3.
        ->assertSeeInOrder(['Humor', 'nem illeszkedik az Értéktárra', 'nem az enyém', 'nevettünk egyet'])
        ->assertSee(route('admin.values.edit', $value))
        ->assertSee(route('admin.materials.show', $mirror->material));
});

it('links the account that waits under the e-mail before the confirmation', function () {
    actingAsAdmin();
    $pending = User::factory()->create(['email' => 'anna@example.com', 'activated_at' => null]);
    $mirror = MirrorSession::factory()->create(['email' => 'anna@example.com', 'confirmation_sent_at' => now()]);

    Livewire::test(Show::class, ['mirror' => $mirror])
        ->assertSee(route('admin.clients.show', $pending));
});

it('says the values are still being read while the mirror is processing', function () {
    actingAsAdmin();
    $mirror = MirrorSession::factory()->create(['status' => MirrorStatus::Processing]);

    Livewire::test(Show::class, ['mirror' => $mirror])
        ->assertSee('Még dolgozunk rajta.')
        ->assertDontSee('Nem találtunk értéket.');
});
