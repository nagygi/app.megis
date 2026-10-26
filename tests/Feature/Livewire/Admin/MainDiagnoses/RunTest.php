<?php

use App\Enums\MainDiagnosisStage;
use App\Jobs\DraftReading;
use App\Livewire\Admin\Diagnoses\Index;
use App\Livewire\Admin\MainDiagnoses\Run;
use App\Mail\DiagnosisNoticeMail;
use App\Models\InboxItem;
use App\Models\MegisConversation;
use App\Models\ReminderDispatch;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(fn () => Queue::fake());

it('shows the run of the diagnosis in numbers to the contact (19 · 19.3)', function () {
    [$setup, $main] = runningDiagnosis();
    actingAsAdmin($setup['admin']);

    Livewire::test(Run::class, ['diagnosis' => $main->diagnosis])
        ->assertSee('Időablak')
        ->assertSee('A vezető · '.$setup['leader']->name)
        ->assertSee('Társak')
        ->assertSee('A küszöbhöz még 3 kell.')
        ->assertSee('Beszélgetések a mégissel')
        ->assertSee('Olvasat indítása');
});

it('keeps the Futás tab to the contact and the superadmin (19 · 19.1)', function () {
    [$setup, $main] = runningDiagnosis();

    actingAsAdmin(User::factory()->admin()->create());
    $this->get(route('admin.main-diagnoses.run', $main->diagnosis))->assertForbidden();

    actingAsAdmin(User::factory()->superadmin()->create());
    $this->get(route('admin.main-diagnoses.run', $main->diagnosis))->assertOk()->assertSee('Futás');
});

it('opens a main diagnosis on the tab of its stage (19 · 19.1)', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsAdmin($setup['admin']);

    $this->get(route('admin.main-diagnoses.open', $main->diagnosis))->assertRedirect(route('admin.main-diagnoses.edit', $main->diagnosis));

    [$setup, $main] = runningDiagnosis();
    actingAsAdmin($setup['admin']);

    $this->get(route('admin.main-diagnoses.open', $main->diagnosis))->assertRedirect(route('admin.main-diagnoses.run', $main->diagnosis));
});

it('reminds the peers who have not answered, at most once in 48 hours (19 · 19.4)', function () {
    Mail::fake();
    [$setup, $main] = runningDiagnosis();
    actingAsAdmin($setup['admin']);

    Livewire::test(Run::class, ['diagnosis' => $main->diagnosis])
        ->call('remindPeers')
        ->assertSee('Emlékeztető elküldve: 2 társ.');

    Mail::assertQueued(DiagnosisNoticeMail::class, 2);
    expect(ReminderDispatch::query()->where('kind', ReminderDispatch::PEER)->count())->toBe(2);

    Livewire::test(Run::class, ['diagnosis' => $main->diagnosis])
        ->call('remindPeers')
        ->assertSee('Emlékeztető elküldve: 0 társ. 48 órán belül már kapott: 2.');

    $this->travel(49)->hours();
    Livewire::test(Run::class, ['diagnosis' => $main->diagnosis])->call('remindPeers');

    Mail::assertQueued(DiagnosisNoticeMail::class, 4);
});

it('reminds the leader who has not finished filling in', function () {
    Mail::fake();
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsAdmin($setup['admin']);

    Livewire::test(Run::class, ['diagnosis' => $main->diagnosis])
        ->call('remindLeader')
        ->assertSee('Elküldtük az emlékeztetőt a vezetőnek.')
        ->call('remindLeader')
        ->assertSee('48 órán belül');

    Mail::assertQueued(DiagnosisNoticeMail::class, fn (DiagnosisNoticeMail $mail) => $mail->hasTo($setup['leader']->email) && str_contains($mail->body, 'lépés van hátra'));
    Mail::assertQueued(DiagnosisNoticeMail::class, 1);
});

it('flags the threshold a week before publishing and records the decisions (19 · 19.4)', function () {
    Mail::fake();
    [$setup, $main] = runningDiagnosis();
    $main->update(['publishes_on' => today()->addDays(5)]);
    actingAsAdmin($setup['admin']);

    $component = Livewire::test(Run::class, ['diagnosis' => $main->diagnosis])
        ->assertSee('A küszöb veszélyben. Mit teszel?')
        ->call('composeThreshold')
        ->assertSet('thresholdBody', fn (string $body) => str_contains($body, 'legalább 3 társ'))
        ->set('thresholdBody', 'Szólnál a csapatnak? Még 3 válasz kell.')
        ->call('sendThreshold')
        ->assertSee('Elküldtük a vezetőnek.');

    Mail::assertQueued(DiagnosisNoticeMail::class, fn (DiagnosisNoticeMail $mail) => $mail->body === 'Szólnál a csapatnak? Még 3 válasz kell.');

    $component->call('snoozeThreshold')->assertDontSee('A küszöb veszélyben.');
    expect($main->refresh()->threshold_snoozed_until->toDateString())->toBe(today()->addDays(5)->toDateString());

    $this->travel(6)->days();
    $main->update(['publishes_on' => today()->addDays(3)]);

    Livewire::test(Run::class, ['diagnosis' => $main->diagnosis])
        ->assertSee('A küszöb veszélyben.')
        ->call('overrideThreshold')
        ->assertSee('Csapat-szám nélkül megy · '.$setup['admin']->name)
        ->assertDontSee('A küszöb veszélyben.')
        ->call('revokeThresholdOverride')
        ->assertSee('A küszöb veszélyben.');
});

it('lets a reading started below the threshold go without the team number (19 · 19.4)', function () {
    [$setup, $main] = runningDiagnosis();
    actingAsAdmin($setup['admin']);

    Livewire::test(Run::class, ['diagnosis' => $main->diagnosis])
        ->assertSee('A küszöb nem teljesült.')
        ->call('startReading');
    expect($main->refresh()->stage)->toBe(MainDiagnosisStage::Reading);

    Livewire::test(Run::class, ['diagnosis' => $main->diagnosis])
        ->assertSee('A küszöb nem teljesült.')
        ->call('overrideThreshold')
        ->assertSee('Csapat-szám nélkül megy · '.$setup['admin']->name)
        ->assertDontSee('A küszöb nem teljesült.');
    expect($main->refresh()->threshold_overridden_by)->toBe($setup['admin']->id);
});

it('moves the publishing day and tells the leader (19 · 19.4)', function () {
    Mail::fake();
    [$setup, $main] = runningDiagnosis();
    actingAsAdmin($setup['admin']);
    $date = today()->addWeeks(8)->toDateString();

    Livewire::test(Run::class, ['diagnosis' => $main->diagnosis])
        ->set('publishesOn', today()->subDay()->toDateString())
        ->call('changePublishDate')
        ->assertHasErrors(['publishesOn'])
        ->set('publishesOn', $date)
        ->call('changePublishDate')
        ->assertHasNoErrors();

    expect($main->refresh()->publishes_on->toDateString())->toBe($date);
    Mail::assertQueued(DiagnosisNoticeMail::class, fn (DiagnosisNoticeMail $mail) => $mail->hasTo($setup['leader']->email) && str_contains($mail->body, today()->addWeeks(8)->format('Y.m.d.')));
});

it('starts the reading from the Futás tab (19 · 19.4)', function () {
    [$setup, $main] = runningDiagnosis();
    actingAsAdmin($setup['admin']);

    Livewire::test(Run::class, ['diagnosis' => $main->diagnosis])
        ->call('startReading')
        ->assertRedirect(route('admin.diagnoses.show', $main->diagnosis));

    expect($main->refresh()->stage)->toBe(MainDiagnosisStage::Reading);
    Queue::assertPushed(DraftReading::class);
});

it('records the conversations of the mégis (19 · 19.5)', function () {
    [$setup, $main] = runningDiagnosis();
    actingAsAdmin($setup['admin']);

    Livewire::test(Run::class, ['diagnosis' => $main->diagnosis])
        ->set('conversation.held_at', now()->subDay()->format('Y-m-d\TH:i'))
        ->set('conversation.with_kind', 'external')
        ->call('addConversation')
        ->assertHasErrors(['conversation.with_name'])
        ->set('conversation.with_name', 'Külsős Kata')
        ->set('conversation.note', 'Első beszélgetés')
        ->call('addConversation')
        ->assertHasNoErrors()
        ->assertSee('Külsős Kata')
        ->assertSee('1 megtartva');

    $conversation = MegisConversation::query()->sole();
    expect($conversation->with_kind)->toBe('external')
        ->and($conversation->admin_id)->toBe($setup['admin']->id);
});

it('shows the main diagnoses in the garden by stage (19 · 19.2)', function () {
    [$setup, $main] = runningDiagnosis();
    $invited = startMainDiagnosis(diagnosisTeam());
    InboxItem::factory()->create(['diagnosis_id' => $main->diagnosis_id]);
    actingAsAdmin($setup['admin']);

    Livewire::test(Index::class)
        ->assertSee('Meghívva')
        ->assertSee('Fut')
        ->assertSee($main->diagnosis->name)
        ->assertSee($invited->diagnosis->name)
        ->assertSee('0 / 2 társ')
        ->assertSeeHtml('border-2 border-coral')
        ->assertSeeHtml(route('admin.main-diagnoses.open', $main->diagnosis))
        ->set('subject', 'person')
        ->assertDontSee($main->diagnosis->name)
        ->set('subject', '')
        ->set('organization', (string) $invited->diagnosis->organization_id)
        ->assertSee($invited->diagnosis->name)
        ->assertDontSee($main->diagnosis->name)
        ->set('organization', '')
        ->set('view', 'lista')
        ->assertSee(__('admin.diagnoses.name'));
});
