<?php

use App\Enums\AuditEventType;
use App\Livewire\Admin\Clients\Index;
use App\Livewire\Admin\Clients\Show;
use App\Mail\EmailChangedMail;
use App\Models\AuditEvent;
use App\Models\Impersonation;
use App\Models\MainDiagnosisParticipant;
use App\Models\MirrorSession;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('lets a superadmin change the e-mail, telling both addresses and logging it', function () {
    Mail::fake();
    $superadmin = actingAsAdmin(User::factory()->superadmin()->create());
    $customer = customerOf();
    $oldEmail = $customer->email;

    Livewire::test(Show::class, ['user' => $customer])
        ->call('startEditing')
        ->set('email', 'uj.cim@example.com')
        ->call('save')
        ->assertHasNoErrors();

    expect($customer->fresh()->email)->toBe('uj.cim@example.com');
    Mail::assertQueued(EmailChangedMail::class, fn (EmailChangedMail $mail) => $mail->hasTo($oldEmail) && $mail->toOldAddress);
    Mail::assertQueued(EmailChangedMail::class, fn (EmailChangedMail $mail) => $mail->hasTo('uj.cim@example.com') && ! $mail->toOldAddress);
    expect(AuditEvent::query()->sole())
        ->type->toBe(AuditEventType::EmailChanged)
        ->actor_id->toBe($superadmin->id);
});

it('forbids an admin from changing a customer e-mail', function () {
    actingAsAdmin();
    $customer = customerOf();

    Livewire::test(Show::class, ['user' => $customer])
        ->call('startEditing')
        ->set('email', 'uj.cim@example.com')
        ->call('save')
        ->assertForbidden();

    expect($customer->fresh()->email)->not->toBe('uj.cim@example.com');
});

it('lets any admin correct the name', function () {
    actingAsAdmin();
    $customer = customerOf();

    Livewire::test(Show::class, ['user' => $customer])
        ->call('startEditing')
        ->set('last_name', 'Javított')
        ->call('save')
        ->assertHasNoErrors();

    expect($customer->fresh()->last_name)->toBe('Javított');
});

it('lets only who may edit an admin account rename it on the datasheet (33 · 33.2)', function () {
    actingAsAdmin();
    $superadmin = User::factory()->superadmin()->create(['last_name' => 'Fonok']);

    Livewire::test(Show::class, ['user' => $superadmin])
        ->assertDontSee('Szerkesztem')
        ->call('startEditing')
        ->assertForbidden();
    Livewire::test(Show::class, ['user' => $superadmin])
        ->set('last_name', 'Atnevezett')
        ->call('save')
        ->assertForbidden();
    expect($superadmin->fresh()->last_name)->toBe('Fonok');

    actingAsAdmin(User::factory()->superadmin()->create());
    Livewire::test(Show::class, ['user' => $superadmin])
        ->call('startEditing')
        ->set('last_name', 'Atnevezett')
        ->call('save')
        ->assertHasNoErrors();
    expect($superadmin->fresh()->last_name)->toBe('Atnevezett');
});

it('forgets the undeliverable mark with a new e-mail address (33 · 33.2)', function () {
    Mail::fake();
    actingAsAdmin(User::factory()->superadmin()->create());
    $customer = customerOf();
    $customer->forceFill(['mail_undeliverable_at' => now()])->save();

    Livewire::test(Show::class, ['user' => $customer])
        ->call('startEditing')
        ->set('last_name', 'Javított')
        ->call('save');
    expect($customer->fresh()->mail_undeliverable_at)->not->toBeNull();

    Livewire::test(Show::class, ['user' => $customer])
        ->call('startEditing')
        ->set('email', 'uj.cim@example.com')
        ->call('save')
        ->assertHasNoErrors();
    expect($customer->fresh()->mail_undeliverable_at)->toBeNull();
});

it('shows when the user asked for a booking on their Értéktükör (33 · 33.4)', function () {
    actingAsAdmin();
    $customer = customerOf();
    MirrorSession::factory()->create([
        'user_id' => $customer->id,
        'claimed_at' => now(),
        'booking_requested_at' => now()->setDate(2026, 10, 20)->setTime(14, 5),
    ]);

    Livewire::test(Show::class, ['user' => $customer])->assertSee('időpontot foglalna · 2026.10.20. 14:05');
});

it('pages the log, the newest first, and tells what each entry was (33 · 33.6)', function () {
    $admin = actingAsAdmin();
    $customer = customerOf();
    $logger = app(AuditLogger::class);

    foreach (range(1, 50) as $day) {
        $logger->record(AuditEventType::RecordingDeleted, $customer, $customer, ['date' => sprintf('nap-%02d', $day)], at: now()->subDays(100 - $day));
    }
    $logger->record(AuditEventType::ImpersonationAction, $customer, $admin, ['actions' => ['client.diagnosis.own-values::save', 'client.unknown::run']]);
    $logger->record(AuditEventType::ImpersonationEnded, $customer, $admin, ['reason' => Impersonation::END_ABANDONED]);

    Livewire::test(Show::class, ['user' => $customer])
        ->assertSee('Módosítások: Diagnózis · saját értékek (save), client.unknown (run)')
        ->assertSee('Félbehagyva: az admin Kilépés nélkül ment el.')
        ->assertSee('A felvétel napja: nap-50')
        ->assertDontSee('A felvétel napja: nap-01')
        ->call('nextPage', 'naplo')
        ->assertSee('A felvétel napja: nap-01')
        ->assertDontSee('A felvétel napja: nap-50');
});

it('finds a user by the full name, in either order (33 · 33.8)', function () {
    actingAsAdmin();
    customerOf()->update(['last_name' => 'Kovács', 'first_name' => 'Anna']);
    customerOf()->update(['last_name' => 'Kovács', 'first_name' => 'Béla']);

    Livewire::test(Index::class)->set('search', 'Kovács Anna')->assertSee('Kovács Anna')->assertDontSee('Kovács Béla');
    Livewire::test(Index::class)->set('search', 'anna kovács')->assertSee('Kovács Anna')->assertDontSee('Kovács Béla');
});

it('filters the users who only made an Értéktükör (33 · 33.8)', function () {
    actingAsAdmin();
    $mirrorOnly = customerOf();
    $mirrorOnly->update(['last_name' => 'Tukros']);
    MirrorSession::factory()->create(['user_id' => $mirrorOnly->id, 'claimed_at' => now()]);
    $alsoPeer = customerOf();
    $alsoPeer->update(['last_name' => 'Tarsas']);
    MirrorSession::factory()->create(['user_id' => $alsoPeer->id, 'claimed_at' => now()]);
    MainDiagnosisParticipant::factory()->create(['user_id' => $alsoPeer->id]);
    customerOf()->update(['last_name' => 'Tukortelen']);

    Livewire::test(Index::class)
        ->set('filters.mirror', 'only')
        ->assertSee('Tukros')
        ->assertDontSee('Tarsas')
        ->assertDontSee('Tukortelen');
});

it('hides a deleted user\'s technical address on the lists (33 · 33.7)', function () {
    actingAsAdmin();
    $customer = customerOf();
    $customer->forceFill(['email' => 'deleted-'.$customer->id.'@deleted.invalid', 'anonymized_at' => now()])->save();

    Livewire::test(Index::class)->assertSee('Törölt felhasználó · #'.$customer->id)->assertDontSee('@deleted.invalid');
});

it('lists customers, not pure admins', function () {
    actingAsAdmin();
    customerOf()->update(['last_name' => 'Ugyfelnev']);
    User::factory()->admin()->create(['last_name' => 'Csakadmin']);

    $component = Livewire::test(Index::class);

    $component->assertSee('Ugyfelnev')->assertDontSee('Csakadmin');
});
