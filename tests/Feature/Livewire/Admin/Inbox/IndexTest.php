<?php

use App\Enums\InboxItemType;
use App\Livewire\Admin\Inbox\Index;
use App\Models\Diagnosis;
use App\Models\InboxItem;
use App\Models\Organization;
use App\Models\User;
use App\Services\Inbox\InboxService;
use Livewire\Livewire;

it('says there is nothing to do when the list is empty', function () {
    actingAsAdmin();

    Livewire::test(Index::class)->assertSee('Minden rendben. Mehetsz kávézni.');
});

it('starts with the rows of the admin’s own organizations and the unassigned ones', function () {
    $admin = actingAsAdmin();
    $colleague = User::factory()->admin()->create();
    $mine = Organization::factory()->create(['contact_admin_id' => $admin->id]);
    $theirs = Organization::factory()->create(['contact_admin_id' => $colleague->id]);
    $nobodys = Organization::factory()->create();
    $inbox = app(InboxService::class);
    $inbox->open(InboxItemType::Invitation, 'Saját sor', $mine);
    $inbox->open(InboxItemType::Invitation, 'Kolléga sora', $theirs);
    $inbox->open(InboxItemType::Invitation, 'Gazdátlan sor', $nobodys);
    $inbox->open(InboxItemType::MirrorBooking, 'Foglalás szervezet nélkül');

    Livewire::test(Index::class)
        ->assertSee('Saját sor')
        ->assertSee('Gazdátlan sor')
        ->assertSee('Foglalás szervezet nélkül')
        ->assertDontSee('Kolléga sora')
        ->set('filters.contact', 'all')
        ->assertSee('Kolléga sora');
});

it('closes a row and shows who closed it under "Lezártak"', function () {
    $admin = actingAsAdmin();
    $item = app(InboxService::class)->open(InboxItemType::ProcessingError, 'Nem sikerült feldolgozni egy anyagot.');

    Livewire::test(Index::class)
        ->call('close', $item->id)
        ->assertDontSee('Nem sikerült feldolgozni egy anyagot.')
        ->set('filters.state', 'closed')
        ->assertSee('Nem sikerült feldolgozni egy anyagot.')
        ->assertSee($admin->name);

    expect($item->fresh())->closed_by->toBe($admin->id)->close_reason->toBe(InboxItem::CLOSED_MANUALLY);
});

it('counts the open rows in the menu', function () {
    actingAsAdmin();
    InboxItem::factory()->count(2)->create();
    InboxItem::factory()->closed()->create();

    $this->get(route('admin.organizations.index'))
        ->assertOk()
        ->assertSeeInOrder(['Beérkező', '2']);
});

it('does not open the same question twice and closes it when the cause goes away', function () {
    $inbox = app(InboxService::class);
    $diagnosis = Diagnosis::factory()->create();

    $first = $inbox->open(InboxItemType::Threshold, 'A küszöb veszélyben van.', diagnosis: $diagnosis, dedupeKey: 'threshold:'.$diagnosis->id);
    $second = $inbox->open(InboxItemType::Threshold, 'A küszöb veszélyben van.', diagnosis: $diagnosis, dedupeKey: 'threshold:'.$diagnosis->id);

    expect($second->id)->toBe($first->id)
        ->and($first->organization_id)->toBe($diagnosis->organization_id)
        ->and($inbox->closeAutomatically('threshold:'.$diagnosis->id))->toBe(1)
        ->and($first->fresh()->close_reason)->toBe(InboxItem::CLOSED_AUTOMATICALLY)
        ->and($inbox->open(InboxItemType::Threshold, 'Újra veszélyben.', diagnosis: $diagnosis, dedupeKey: 'threshold:'.$diagnosis->id)->id)->not->toBe($first->id);
});
