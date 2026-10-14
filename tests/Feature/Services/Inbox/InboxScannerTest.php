<?php

use App\Enums\InboxItemType;
use App\Enums\MainDiagnosisStage;
use App\Enums\ParticipantRole;
use App\Livewire\Admin\Inbox\Index;
use App\Mail\DiagnosisNoticeMail;
use App\Mail\DiagnosisPeerInvitationMail;
use App\Mail\InboxDigestMail;
use App\Mail\MirrorBookingMail;
use App\Models\InboxItem;
use App\Models\MirrorSession;
use App\Models\Process;
use App\Models\ProcessOccasion;
use App\Services\Inbox\InboxScanner;
use App\Services\MainDiagnoses\MainDiagnosisService;
use App\Services\MainDiagnoses\StageMachine;
use App\Services\Mirror\MirrorService;
use App\Services\Processes\OccasionService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(fn () => Queue::fake());

it('opens a row for an invitation not opened in five days and closes it once opened (22 · 22.2)', function () {
    [$setup, $main] = runningDiagnosis();
    $peer = $main->participantFor($setup['peers'][0], ParticipantRole::Peer);

    app(InboxScanner::class)->scan();
    expect(InboxItem::query()->where('type', InboxItemType::Invitation)->count())->toBe(0);

    $this->travel(5)->days();
    app(InboxScanner::class)->scan();
    app(InboxScanner::class)->scan();

    $item = InboxItem::query()->where('type', InboxItemType::Invitation)->where('subject_id', $peer->id)->sole();
    expect($item->summary)->toContain($setup['peers'][0]->name)->toContain('5 napja');

    app(MainDiagnosisService::class)->opened($main, $peer);

    expect($item->refresh()->closed_at)->not->toBeNull()
        ->and($item->close_reason)->toBe(InboxItem::CLOSED_AUTOMATICALLY);
});

it('opens a row when the leader has not started by the starting day, and closes it on start', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup, overrides: ['starts_on' => today()->addDay()->toDateString()]);

    app(InboxScanner::class)->scan();
    expect(InboxItem::query()->where('type', InboxItemType::Completion)->exists())->toBeFalse();

    $this->travel(1)->days();
    app(InboxScanner::class)->scan();

    $item = InboxItem::query()->where('type', InboxItemType::Completion)->sole();
    expect($item->summary)->toContain($setup['leader']->name);

    app(StageMachine::class)->transition($main->refresh(), MainDiagnosisStage::Filling);
    app(StageMachine::class)->transition($main->refresh(), MainDiagnosisStage::Running);

    expect($item->refresh()->closed_at)->not->toBeNull();
});

it('opens threshold and publication rows and closes them when published', function () {
    [$setup, $main] = runningDiagnosis();
    $main->update(['publishes_on' => today()->addDays(3)]);

    app(InboxScanner::class)->scan();
    expect(InboxItem::query()->where('type', InboxItemType::Threshold)->exists())->toBeTrue();

    $this->travel(4)->days();
    app(InboxScanner::class)->scan();
    expect(InboxItem::query()->where('type', InboxItemType::Publication)->exists())->toBeTrue();

    app(StageMachine::class)->transition($main->refresh(), MainDiagnosisStage::Reading, $setup['admin']);
    app(StageMachine::class)->transition($main->refresh(), MainDiagnosisStage::Published, $setup['admin']);

    expect(InboxItem::query()->open()->count())->toBe(0);
});

it('mails each contact the open rows of their organizations at 8:00', function () {
    Mail::fake();
    [$setup, $main] = runningDiagnosis();
    InboxItem::factory()->create(['organization_id' => $setup['organization']->id, 'diagnosis_id' => $main->diagnosis_id]);
    InboxItem::factory()->closed()->create(['organization_id' => $setup['organization']->id]);

    $this->artisan('megis:inbox:digest')->assertSuccessful();

    Mail::assertQueued(InboxDigestMail::class, fn (InboxDigestMail $mail) => $mail->hasTo($setup['admin']->email) && $mail->items->count() === 1);
    Mail::assertQueued(InboxDigestMail::class, 1);
});

it('mails a booking at once to the contact', function () {
    Mail::fake();
    [$setup] = runningDiagnosis();
    $session = MirrorSession::factory()->create(['user_id' => $setup['leader']->id, 'organization_id' => $setup['organization']->id]);

    app(MirrorService::class)->recordBooking($session);
    app(MirrorService::class)->recordBooking($session->refresh());

    Mail::assertQueued(MirrorBookingMail::class, fn (MirrorBookingMail $mail) => $mail->hasTo($setup['admin']->email));
    Mail::assertQueued(MirrorBookingMail::class, 1);
});

it('classifies from the Beérkező and the row leaves the tab too (22 · 22.2)', function () {
    Storage::fake('materials');
    [$setup, $main] = runningDiagnosis();
    $process = Process::factory()->accepted()->for($setup['organization'])->create(['name' => 'Retró']);
    $material = app(OccasionService::class)->situationText($main, $setup['leader'], 'A retrón mindenki elmondta, mi akadt el a héten.');
    app(OccasionService::class)->place($material, null, today()->toDateString(), $setup['leader'], false);
    $item = InboxItem::query()->sole();
    actingAsAdmin($setup['admin']);

    Livewire::test(Index::class)
        ->assertSee('Retró')
        ->set("classify.{$item->id}.process_id", (string) $process->id)
        ->call('classifyMaterial', $item->id)
        ->assertHasNoErrors();

    expect($item->refresh()->closed_at)->not->toBeNull()
        ->and(ProcessOccasion::query()->sole()->process_id)->toBe($process->id)
        ->and($material->refresh()->process_occasion_id)->not->toBeNull();
});

it('reminds and re-invites from an invitation row', function () {
    Mail::fake();
    [$setup, $main] = runningDiagnosis();
    $this->travel(5)->days();
    app(InboxScanner::class)->scan();
    $item = InboxItem::query()->where('type', InboxItemType::Invitation)->where('subject_id', $main->participantFor($setup['peers'][0], ParticipantRole::Peer)->id)->sole();
    actingAsAdmin($setup['admin']);

    Livewire::test(Index::class)
        ->call('remind', $item->id)
        ->assertSee('Elküldtük az emlékeztetőt.')
        ->call('remind', $item->id)
        ->assertSee('48 órán belül')
        ->call('reinvite', $item->id);

    Mail::assertQueued(DiagnosisNoticeMail::class, 1);
    Mail::assertQueued(DiagnosisPeerInvitationMail::class);
    expect($item->refresh()->closed_at)->not->toBeNull();
});
