<?php

use App\Enums\MaterialContext;
use App\Enums\MirrorStatus;
use App\Livewire\Admin\Mirrors\Index;
use App\Models\Material;
use App\Models\MirrorSession;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

/**
 * A mirror from the own menu: the user's from the start (06 · 6.2).
 */
function ownMenuMirror(User $user, array $attributes = []): MirrorSession
{
    return MirrorSession::factory()->create([
        'material_id' => Material::factory()->state(['context' => MaterialContext::Mirror]),
        'user_id' => $user->id,
        'organization_id' => Organization::factory(),
        'status' => MirrorStatus::Claimed,
        'claimed_at' => now(),
        'expires_at' => null,
        ...$attributes,
    ]);
}

it('lists every mirror, the public one waiting for the e-mail confirmation too', function () {
    actingAsAdmin();
    MirrorSession::factory()->create(['email' => 'anna@example.com', 'confirmation_sent_at' => now()]);
    $owner = User::factory()->create();
    ownMenuMirror($owner, ['created_at' => now()->subHour()]);

    // The newest first; each row says where it came from and whose it is.
    Livewire::test(Index::class)
        ->assertSeeInOrder(['anna@example.com', 'Nyilvános Értéktükör', 'Megerősítésre vár', $owner->name, 'Értéktükör', 'Fiókban']);
});

it('narrows the list by whose the mirror is', function (string $state, string $email) {
    actingAsAdmin();
    MirrorSession::factory()->create(['email' => 'anna@example.com', 'confirmation_sent_at' => now()]);
    // The address belongs to an active account: no mail, the user signs in on ET5.
    MirrorSession::factory()->create(['email' => 'bela@example.com']);
    MirrorSession::factory()->create(['email' => 'cecil@example.com', 'status' => MirrorStatus::Claimed, 'claimed_at' => now(), 'expires_at' => null]);

    $list = Livewire::test(Index::class)->set('filters.account', $state)->assertSee($email);

    foreach (array_diff(['anna@example.com', 'bela@example.com', 'cecil@example.com'], [$email]) as $other) {
        $list->assertDontSee($other);
    }
})->with([
    'waiting for the confirmation' => ['awaiting_confirmation', 'anna@example.com'],
    'waiting for the sign-in' => ['awaiting_login', 'bela@example.com'],
    'in an account' => ['claimed', 'cecil@example.com'],
]);

it('counts a claimed mirror as done, not a failed or a running one', function () {
    actingAsAdmin();
    MirrorSession::factory()->create(['email' => 'ready@example.com', 'status' => MirrorStatus::Ready]);
    MirrorSession::factory()->create(['email' => 'claimed@example.com', 'status' => MirrorStatus::Claimed, 'claimed_at' => now()]);
    MirrorSession::factory()->create(['email' => 'failed@example.com', 'status' => MirrorStatus::Failed]);
    MirrorSession::factory()->create(['email' => 'running@example.com', 'status' => MirrorStatus::Processing]);

    Livewire::test(Index::class)
        ->set('filters.status', 'ready')
        ->assertSee('ready@example.com')
        ->assertSee('claimed@example.com')
        ->assertDontSee('failed@example.com')
        ->assertDontSee('running@example.com');
});

it('narrows the list to the mirrors of the own menu', function () {
    actingAsAdmin();
    MirrorSession::factory()->create(['email' => 'anna@example.com']);
    $owner = User::factory()->create(['email' => 'bela@example.com']);
    ownMenuMirror($owner);

    Livewire::test(Index::class)
        ->set('filters.source', MaterialContext::Mirror->value)
        ->assertSee($owner->name)
        ->assertDontSee('anna@example.com');
});

it('finds a mirror by the e-mail or by the user’s name', function () {
    actingAsAdmin();
    MirrorSession::factory()->create(['email' => 'anna@example.com']);
    $owner = User::factory()->create(['last_name' => 'Kovácsházi']);
    ownMenuMirror($owner);

    Livewire::test(Index::class)
        ->set('search', 'anna@')
        ->assertSee('anna@example.com')
        ->assertDontSee($owner->name)
        ->set('search', 'Kovácsházi')
        ->assertSee($owner->name)
        ->assertDontSee('anna@example.com');
});

it('says when an unconfirmed mirror has expired but is not pruned yet', function () {
    actingAsAdmin();
    MirrorSession::factory()->create(['email' => 'anna@example.com', 'confirmation_sent_at' => now()->subDay(), 'expires_at' => now()->subMinute()]);

    Livewire::test(Index::class)->assertSeeInOrder(['anna@example.com', 'lejárt']);
});

it('keeps the mirrors for the admins', function () {
    $mirror = MirrorSession::factory()->create();

    $this->get(route('admin.mirrors.index'))->assertRedirect(route('admin.login'));
    $this->get(route('admin.mirrors.show', $mirror))->assertRedirect(route('admin.login'));
});
