<?php

use App\Enums\InboxItemType;
use App\Enums\MaterialContext;
use App\Livewire\Admin\Inbox\Index;
use App\Livewire\Admin\Measurements\Index as MeasurementsIndex;
use App\Models\Diagnosis;
use App\Models\InboxItem;
use App\Models\Material;
use App\Models\MirrorSession;
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
    $inbox->open(InboxItemType::ProcessingError, 'Hiba szervezet nélkül');

    Livewire::test(Index::class)
        ->assertSee('Saját sor')
        ->assertSee('Gazdátlan sor')
        ->assertSee('Hiba szervezet nélkül')
        ->assertDontSee('Kolléga sora')
        ->set('filters.contact', 'all')
        ->assertSee('Kolléga sora');
});

it('lists the Értéktükör rows under Értékmérések, not in the Beérkező', function () {
    actingAsAdmin();
    $inbox = app(InboxService::class);
    $booking = $inbox->open(InboxItemType::MirrorBooking, 'Valaki időpontot foglalna', subject: MirrorSession::factory()->create());
    $mirrorFailure = $inbox->open(InboxItemType::ProcessingError, 'Nem tudtuk feldolgozni: Nyilvános Értéktükör', subject: Material::factory()->create(['context' => MaterialContext::PublicMirror]));
    $uploadFailure = $inbox->open(InboxItemType::ProcessingError, 'Nem tudtuk feldolgozni: interju.mp3', subject: Material::factory()->create(['context' => MaterialContext::AdminUpload]));

    expect($booking->is_measurement)->toBeTrue()
        ->and($mirrorFailure->is_measurement)->toBeTrue()
        ->and($uploadFailure->is_measurement)->toBeFalse();

    Livewire::test(Index::class)
        ->assertSee('interju.mp3')
        ->assertDontSee('Valaki időpontot foglalna')
        ->assertDontSee('Nyilvános Értéktükör')
        ->assertDontSee(InboxItemType::MirrorBooking->label());

    Livewire::test(MeasurementsIndex::class)
        ->assertSee('Értékmérések')
        ->assertSee('Valaki időpontot foglalna')
        ->assertSee('Nyilvános Értéktükör')
        ->assertDontSee('interju.mp3')
        ->call('close', $booking->id)
        ->assertDontSee('Valaki időpontot foglalna')
        ->set('filters.state', 'closed')
        ->assertSee('Valaki időpontot foglalna');
});

it('opens the mirror from its booking row, the unconfirmed one too', function () {
    actingAsAdmin();
    $mirror = MirrorSession::factory()->create(['email' => 'anna@example.com', 'confirmation_sent_at' => now()]);
    app(InboxService::class)->open(InboxItemType::MirrorBooking, 'anna@example.com időpontot foglalna', subject: $mirror);

    Livewire::test(MeasurementsIndex::class)->assertSee(route('admin.mirrors.show', $mirror));
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

it('counts only the rows the list shows by default, not a colleague’s', function () {
    $admin = actingAsAdmin();
    $theirs = Organization::factory()->create(['contact_admin_id' => User::factory()->admin()->create()->id]);
    InboxItem::factory()->create(['organization_id' => $theirs->id]);
    InboxItem::factory()->create(['organization_id' => $theirs->id, 'type' => InboxItemType::MirrorBooking, 'is_measurement' => true]);

    expect(app(InboxService::class))
        ->openCount($admin)->toBe(0)
        ->openMeasurementCount($admin)->toBe(0);

    $this->get(route('admin.diagnoses.index'))
        ->assertOk()
        ->assertSee('Ma 0 dolog vár rád.');
});

it('counts the open Értékmérések rows under their own group in the menu', function () {
    actingAsAdmin();
    InboxItem::factory()->create();
    InboxItem::factory()->count(3)->create(['type' => InboxItemType::MirrorBooking, 'is_measurement' => true]);

    $this->get(route('admin.organizations.index'))
        ->assertOk()
        ->assertSeeInOrder(['Beérkező', '1', 'Értékmérések', 'Beérkező', '3']);
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
