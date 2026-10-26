<?php

use App\Enums\AuditEventType;
use App\Livewire\Admin\Organizations\Processes;
use App\Models\AuditEvent;
use App\Models\CalendarConnection;
use App\Models\Process;
use App\Services\Ai\AiGateway;
use App\Services\Calendar\CalendarService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    AiGateway::fake(fn () => throw new RuntimeException('down'));
});

it("loads the leader's iCal link in their name and logs it", function () {
    $setup = diagnosisTeam(peers: 1);
    $main = startMainDiagnosis($setup);
    $admin = actingAsAdmin($setup['admin']);
    Http::fake(['calendar.example.com/*' => Http::response(sampleIcs($setup['leader']->email, $setup['peers'][0]->email))]);

    Livewire::test(Processes::class, ['organization' => $setup['organization']])
        ->set('icsUrl', 'https://calendar.example.com/secret.ics')
        ->set('leaderAsked', true)
        ->call('connectCalendarUrl')
        ->assertHasNoErrors()
        ->assertSee(__('admin.processes.connected_by', ['name' => $admin->name]));

    $connection = CalendarConnection::query()->sole();
    $event = AuditEvent::query()->sole();
    expect($connection->user_id)->toBe($setup['leader']->id)
        ->and($connection->connected_by_id)->toBe($admin->id)
        ->and($connection->main_diagnosis_id)->toBe($main->id)
        ->and($connection->provider)->toBe(CalendarConnection::ICS_URL)
        ->and(Process::query()->sole()->name)->toBe('Vezetői kör')
        ->and($event->type)->toBe(AuditEventType::CalendarConnectedByAdmin)
        ->and($event->subject_user_id)->toBe($setup['leader']->id)
        ->and($event->actor_id)->toBe($admin->id);
});

it("loads the leader's exported file and replaces their previous calendar", function () {
    $setup = diagnosisTeam(peers: 1);
    $main = startMainDiagnosis($setup);
    $admin = actingAsAdmin($setup['admin']);
    $ics = sampleIcs($setup['leader']->email, $setup['peers'][0]->email);
    $previous = app(CalendarService::class)->connectIcsFile($setup['leader'], $setup['organization'], $main, $ics);

    Livewire::test(Processes::class, ['organization' => $setup['organization']])
        ->set('icsFile', UploadedFile::fake()->createWithContent('naptar.ics', $ics))
        ->set('leaderAsked', true)
        ->call('connectCalendarFile')
        ->assertHasNoErrors();

    $connection = CalendarConnection::query()->latest('id')->first();
    expect($connection->provider)->toBe(CalendarConnection::ICS_FILE)
        ->and($connection->user_id)->toBe($setup['leader']->id)
        ->and($connection->connected_by_id)->toBe($admin->id)
        ->and($previous->refresh()->status)->toBe(CalendarConnection::DISCONNECTED);
});

it('loads nothing unless the leader asked for it', function () {
    $setup = diagnosisTeam(peers: 1);
    startMainDiagnosis($setup);
    actingAsAdmin($setup['admin']);

    Livewire::test(Processes::class, ['organization' => $setup['organization']])
        ->set('icsUrl', 'https://calendar.example.com/secret.ics')
        ->call('connectCalendarUrl')
        ->assertHasErrors(['leaderAsked' => __('admin.processes.leader_asked_required')]);

    expect(CalendarConnection::query()->exists())->toBeFalse();
});

it('shows why an unreadable file was not loaded', function () {
    $setup = diagnosisTeam(peers: 1);
    startMainDiagnosis($setup);
    actingAsAdmin($setup['admin']);

    Livewire::test(Processes::class, ['organization' => $setup['organization']])
        ->set('icsFile', UploadedFile::fake()->createWithContent('naptar.ics', 'nem naptár'))
        ->set('leaderAsked', true)
        ->call('connectCalendarFile')
        ->assertHasErrors(['icsFile' => __('calendar.unreadable')]);

    expect(CalendarConnection::query()->exists())->toBeFalse()
        ->and(AuditEvent::query()->exists())->toBeFalse();
});

it('loads a calendar only for a running diagnosis', function () {
    $setup = diagnosisTeam(peers: 1);
    actingAsAdmin($setup['admin']);

    Livewire::test(Processes::class, ['organization' => $setup['organization']])
        ->assertSee(__('admin.processes.no_running'))
        ->set('icsUrl', 'https://calendar.example.com/secret.ics')
        ->set('leaderAsked', true)
        ->call('connectCalendarUrl')
        ->assertForbidden();
});
