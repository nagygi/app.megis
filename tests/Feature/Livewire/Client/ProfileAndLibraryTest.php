<?php

use App\Enums\AuditEventType;
use App\Livewire\Admin\Clients\Show as Datasheet;
use App\Livewire\Client\Profile;
use App\Livewire\Client\ValueLibrary;
use App\Mail\AccountDeletedMail;
use App\Mail\DiagnosisNoticeMail;
use App\Models\AuditEvent;
use App\Models\EvidenceTrace;
use App\Models\Material;
use App\Models\Organization;
use App\Models\PersonalValueSet;
use App\Models\ReportSnapshot;
use App\Models\User;
use App\Models\Value;
use App\Services\Accounts\AccountDeletionService;
use App\Services\Audit\AuditLogger;
use App\Services\Auth\ImpersonationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    Queue::fake();
    Mail::fake();
});

it('shows the score of the latest report next to the organization\'s values (30 · 30.2)', function () {
    [$setup, $main] = publishedReport();

    actingAsCustomer($setup['leader']);
    Livewire::test(ValueLibrary::class, ['organization' => $setup['organization']])
        ->assertSee('a '.$main->published_at->format('m.d.').'-i diagnózisból');

    actingAsCustomer($setup['peers'][1]);
    Livewire::test(ValueLibrary::class, ['organization' => $setup['organization']])
        ->assertDontSee('-i diagnózisból');
});

it('adds an Értéktár value to the own values (30 · 30.4)', function () {
    $user = customerOf();
    actingAsCustomer($user);
    $value = Value::factory()->create(['reviewed_at' => now()]);
    $value->recordVersion();

    Livewire::test(ValueLibrary::class, ['organization' => $user->accessibleOrganizations()->first()])
        ->call('addToOwn', $value->id)
        ->call('addToOwn', $value->id);

    expect(PersonalValueSet::query()->where('user_id', $user->id)->sole()->items()->pluck('value_id')->all())->toBe([$value->id]);
});

it('shows the leader\'s diagnosis, what waits and the people on the profile (31 · 31.2–31.4)', function () {
    [$setup, $main] = runningDiagnosis();
    actingAsCustomer($setup['leader']);

    Livewire::test(Profile::class, ['organization' => $setup['organization']])
        ->assertSee('A diagnózisod')
        ->assertSee('fut · ')
        ->assertSee('Rád vár')
        ->assertSee('2 társ még nem válaszolt')
        ->assertSee('2 társ · 0 kitöltötte')
        ->call('remindPeers', $main->id);

    Mail::assertQueued(DiagnosisNoticeMail::class, 2);
});

it('deletes the account with the password and purges the data within a day (31 · 31.7)', function () {
    [$setup, $main] = publishedReport();
    $leader = $setup['leader'];
    $leader->forceFill(['password' => 'helyes-jelszo-123'])->save();
    $email = $leader->email;
    actingAsCustomer($leader);

    Livewire::test(Profile::class, ['organization' => $setup['organization']])
        ->set('confirmingDeletion', true)
        ->set('deletionPassword', 'rossz')
        ->call('deleteAccount')
        ->assertHasErrors(['deletionPassword'])
        ->set('deletionPassword', 'helyes-jelszo-123')
        ->call('deleteAccount')
        ->assertRedirect(route('client.login'));

    $leader->refresh();
    expect($leader->deletion_requested_at)->not->toBeNull()
        ->and($leader->deactivated_at)->not->toBeNull()
        ->and(AuditEvent::query()->where('type', AuditEventType::AccountDeletionRequested)->exists())->toBeTrue();

    $this->artisan('megis:accounts:purge')->assertSuccessful();

    $leader->refresh();
    expect($leader->anonymized_at)->not->toBeNull()
        ->and($leader->email)->not->toBe($email)
        ->and($leader->name)->toBe('Törölt felhasználó · #'.$leader->id)
        ->and(Material::query()->where('user_id', $leader->id)->exists())->toBeFalse()
        ->and(EvidenceTrace::query()->where('quote', 'like', '%csúszásról%')->exists())->toBeFalse()
        // The published report gets a correction without the quotes.
        ->and(ReportSnapshot::query()->latest('sequence')->first()->reason)->toBe(ReportSnapshot::CORRECTION);
    Mail::assertQueued(AccountDeletedMail::class, fn ($mail) => $mail->hasTo($email));
});

it('keeps the deletion out of the user\'s name (14 · 14.6)', function () {
    $user = customerOf();
    actingAsCustomer($user);
    app(ImpersonationService::class);
    $this->mock(ImpersonationService::class, fn ($mock) => $mock->shouldReceive('isImpersonating')->andReturn(true)->shouldReceive('current')->andReturn(null));

    Livewire::test(Profile::class, ['organization' => $user->accessibleOrganizations()->first()])
        ->call('deleteAccount')
        ->assertForbidden();
});

it('lists the diagnoses and the consents on the datasheet, and only the log of a deleted user (33)', function () {
    [$setup, $main] = publishedReport();
    actingAsAdmin($setup['admin']);
    $peer = $setup['peers'][0];

    Livewire::test(Datasheet::class, ['user' => $peer])
        ->assertSee($main->diagnosis->name)
        ->assertSee('Társ')
        ->assertSee('A válaszaim névvel csak a mégisnél');
    app(AuditLogger::class)->record(AuditEventType::ConsentGiven, $peer, $peer, ['diagnosis' => $main->diagnosis->name, 'kind' => 'answers'], organization: $setup['organization']);

    app(AccountDeletionService::class)->request($peer);
    app(AccountDeletionService::class)->purge($peer->refresh());

    // The log stays without the names its entries carried (33 · 33.7).
    Livewire::test(Datasheet::class, ['user' => $peer->refresh()])
        ->assertSee('Törölt felhasználó · #'.$peer->id)
        ->assertDontSee($main->diagnosis->name)
        ->assertSee('A válaszaim névvel csak a mégisnél')
        ->assertSee('Fiók és adatok törölve');
    expect(AuditEvent::query()->where('subject_user_id', $peer->id)->where('type', AuditEventType::ConsentGiven)->first()->payload)
        ->toBe(['kind' => 'answers']);
});

it('leaves no log behind a private person, whose organization goes with the account (33 · 33.7)', function () {
    $organization = Organization::factory()->individual()->create();
    $person = customerOf($organization);
    $logger = app(AuditLogger::class);
    $logger->record(AuditEventType::RecordingDeleted, $person, $person, ['date' => '2026.10.20.'], organization: $organization);
    $logger->record(AuditEventType::EmailChanged, $person, User::factory()->superadmin()->create(), ['old' => 'a@example.com', 'new' => 'b@example.com']);

    app(AccountDeletionService::class)->request($person);
    app(AccountDeletionService::class)->purge($person->refresh());

    expect(Organization::query()->whereKey($organization->id)->exists())->toBeFalse()
        ->and(AuditEvent::query()->where('subject_user_id', $person->id)->exists())->toBeFalse();
});
