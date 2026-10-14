<?php

use App\Enums\DiagnosisStep;
use App\Enums\ProcessConfidence;
use App\Enums\ProcessKind;
use App\Enums\ProcessSource;
use App\Enums\StepMark;
use App\Jobs\ExtractConversationProcesses;
use App\Livewire\Admin\Organizations\Processes as AdminProcesses;
use App\Livewire\Client\Diagnosis\ProcessCalendar;
use App\Livewire\Client\Diagnosis\ProcessConversation;
use App\Livewire\Client\Diagnosis\Processes;
use App\Livewire\Client\Diagnosis\ProcessList;
use App\Livewire\Client\Diagnosis\ProcessSummary;
use App\Livewire\Client\ProcessLibrary;
use App\Livewire\Client\ProcessSheet;
use App\Models\CalendarConnection;
use App\Models\Process;
use App\Models\ProcessConversation as Conversation;
use App\Models\Product;
use App\Services\Ai\AiGateway;
use App\Services\Processes\ConversationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\StructuredTextResponse;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Livewire\Livewire;

function processParams(array $setup, $main): array
{
    return ['organization' => $setup['organization'], 'diagnosis' => $main->diagnosis];
}

it('offers the calendar and the conversation, and skipping', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);

    Livewire::test(Processes::class, processParams($setup, $main))
        ->assertSee('Naptárból')
        ->assertSee('Beszélgetésből')
        ->assertSee('Így néz ki egy kész rutin')
        ->call('skip')
        ->assertRedirect(route('client.diagnosis.ki-lat-mit', processParams($setup, $main)));

    expect($main->stepMarks()->where('step', DiagnosisStep::Processes)->value('mark'))->toBe(StepMark::Skipped);
});

it('approves detected processes: rename with a trace, type switch, subprocess, accept', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    $strong = Process::factory()->for($setup['organization'])->fromCalendar()->create(['name' => 'Vezetői kör']);
    $uncertain = Process::factory()->for($setup['organization'])->fromCalendar(ProcessConfidence::Low)->create(['name' => 'Csütörtöki egyeztetés']);
    actingAsCustomer($setup['leader']);

    Livewire::test(ProcessList::class, processParams($setup, $main))
        ->assertSee('Erős minta · 1')
        ->assertSee('Bizonytalan · 1')
        ->call('startRename', $strong->id)
        ->set('renameValue', 'Keddi vezetői kör')
        ->call('saveRename')
        ->call('setKind', $uncertain->id, ProcessKind::Excluded->value)
        ->call('openSub', $strong->id)
        ->set('subName', 'Hibák átbeszélése')
        ->call('addSub')
        ->call('toggleAccept', $strong->id)
        ->assertHasNoErrors();

    expect($strong->refresh()->name)->toBe('Keddi vezetői kör')
        ->and($strong->renames()->sole()->old_name)->toBe('Vezetői kör')
        ->and($strong->renames()->sole()->created_by)->toBe($setup['leader']->id)
        ->and($strong->accepted_at)->not->toBeNull()
        ->and($strong->children()->sole()->name)->toBe('Hibák átbeszélése')
        ->and($uncertain->refresh()->kind)->toBe(ProcessKind::Excluded);
});

it('adds a process by hand, not in the calendar', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);

    Livewire::test(ProcessList::class, processParams($setup, $main))
        ->set('name', 'Havi zárás')
        ->set('rhythm', 'monthly')
        ->call('addManual')
        ->assertHasNoErrors()
        ->assertSee('Kézzel hozzáadva · 1')
        ->assertSee('nincs a naptárban · 0 alkalom');

    expect(Process::query()->sole()->source)->toBe(ProcessSource::Manual);
});

it('measures at most two processes in Snapshot · Belső', function () {
    $setup = diagnosisTeam(product: Product::SNAPSHOT_INTERNAL);
    $main = startMainDiagnosis($setup);
    $processes = Process::factory()->count(3)->for($setup['organization'])->fromCalendar()->create();
    actingAsCustomer($setup['leader']);

    $component = Livewire::test(ProcessList::class, processParams($setup, $main))
        ->assertSee('Még 2 folyamat választható.')
        ->call('acceptAll');

    expect(Process::query()->measured()->count())->toBe(2);

    $component->call('toggleAccept', $processes[2]->id)->assertHasErrors(['accept']);
});

it('closes the step on the summary and records the measured processes', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    $calendar = Process::factory()->for($setup['organization'])->fromCalendar()->accepted()->create(['name' => 'Retró']);
    $manual = Process::factory()->for($setup['organization'])->accepted()->create(['name' => 'Havi zárás']);
    Process::factory()->for($setup['organization'])->create(['name' => 'Kizárt', 'kind' => ProcessKind::Excluded]);
    $setup['organization']->update(['process_recommendation' => 'A Retró legyen benne.']);
    actingAsCustomer($setup['leader']);

    Livewire::test(ProcessSummary::class, processParams($setup, $main))
        ->assertSee('2 folyamat kerül a diagnózisba.')
        ->assertSee('Megvan, de nincs a naptárban')
        ->assertSee('Kimaradt')
        ->assertSee('A Retró legyen benne.')
        ->call('next')
        ->assertRedirect(route('client.diagnosis.ki-lat-mit', processParams($setup, $main)));

    expect($main->processes()->pluck('processes.id')->all())->toEqualCanonicalizing([$calendar->id, $manual->id])
        ->and($main->stepMarks()->where('step', DiagnosisStep::Processes)->value('mark'))->toBe(StepMark::Viewed);
});

it('keeps the data sheet: responsible, participants, teams, own measures with values', function () {
    $setup = diagnosisTeam(peers: 1);
    $main = startMainDiagnosis($setup);
    $process = Process::factory()->for($setup['organization'])->create(['name' => 'Ops heti']);
    actingAsCustomer($setup['leader']);

    $component = Livewire::test(ProcessSheet::class, [...processParams($setup, $main), 'process' => $process])
        ->set('responsibleId', $setup['peers'][0]->id)
        ->set('newParticipantId', $setup['peers'][0]->id)
        ->call('addParticipant')
        ->set('teamIds', [$setup['team']->id])
        ->set('metric.name', 'Lezárt hibák aránya')
        ->set('metric.unit', '%')
        ->set('metric.rhythm', 'weekly')
        ->call('addMetric')
        ->assertHasNoErrors();

    $metric = $process->metrics()->sole();
    $component->call('openRecord', $metric->id)
        ->set('metricValue', '72.5')
        ->call('recordValue')
        ->assertHasNoErrors()
        ->assertSee('Utolsó érték: 72,50 %');

    expect($process->refresh()->responsible_user_id)->toBe($setup['peers'][0]->id)
        ->and($process->participants()->sole()->user_id)->toBe($setup['peers'][0]->id)
        ->and($process->teams()->pluck('teams.id')->all())->toBe([$setup['team']->id])
        ->and($metric->values()->sole()->created_by)->toBe($setup['leader']->id);
});

it('lets the responsible record a value, but not edit the sheet', function () {
    $setup = diagnosisTeam(peers: 1);
    startMainDiagnosis($setup);
    $responsible = $setup['peers'][0];
    $setup['organization']->memberships()->where('user_id', $responsible->id)->update(['is_organization_leader' => true]);
    $process = Process::factory()->for($setup['organization'])->create(['responsible_user_id' => $responsible->id]);
    $metric = $process->metrics()->create(['name' => 'Döntések', 'rhythm' => 'monthly']);
    actingAsCustomer($responsible);

    Livewire::test(ProcessSheet::class, ['organization' => $setup['organization'], 'process' => $process])
        ->call('openRecord', $metric->id)
        ->set('metricValue', '3')
        ->call('recordValue')
        ->assertHasNoErrors();

    expect($metric->values()->count())->toBe(1);
});

it('lists the routines in Tárak → Folyamatok and adds one by hand', function () {
    $setup = diagnosisTeam();
    startMainDiagnosis($setup);
    Process::factory()->for($setup['organization'])->fromCalendar()->accepted()->create(['name' => 'Vezetői kör']);
    actingAsCustomer($setup['leader']);

    Livewire::test(ProcessLibrary::class, ['organization' => $setup['organization']])
        ->assertSee('Vezetői kör')
        ->assertSee('naptárban')
        ->assertSee('mérjük')
        ->set('name', 'Onboarding')
        ->call('add')
        ->assertRedirect();

    expect(Process::query()->where('name', 'Onboarding')->exists())->toBeTrue();
});

it('keeps peers out of the processes', function () {
    $setup = diagnosisTeam(peers: 1, organizationLeader: true);
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['peers'][0]);

    Livewire::test(ProcessLibrary::class, ['organization' => $setup['organization']])->assertForbidden();
});

it('extracts the processes from each answer of the conversation', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);
    Queue::fake();

    Livewire::test(ProcessConversation::class, processParams($setup, $main))
        ->assertSee('Mi az a heti vagy kétheti alkalom, ami ritkán marad el?')
        ->set('answers.1', 'A keddi Vezetői kör, négyen. Meg az ügyfél-review.')
        ->assertHasNoErrors();

    Queue::assertPushed(ExtractConversationProcesses::class, fn ($job) => $job->question === 1);

    $data = ['processes' => [
        ['name' => 'Vezetői kör', 'kind' => 'process', 'rhythm' => 'weekly', 'rhythm_label' => 'keddenként', 'headcount' => 4],
        ['name' => 'döntés négyszemközt', 'kind' => 'unclear', 'rhythm' => null, 'rhythm_label' => null, 'headcount' => null],
    ]];
    AiGateway::fake([new StructuredTextResponse($data, json_encode($data), new TextUsage(10, 5), new Meta('anthropic', 'claude-test'))]);
    $conversation = Conversation::query()->sole();
    app(ConversationService::class)->extract($conversation, 1);

    Livewire::test(ProcessConversation::class, processParams($setup, $main))
        ->assertSee('1 folyamat · 1 tisztázandó')
        ->assertSee('keddenként, 4 fő')
        ->call('finish')
        ->assertRedirect(route('client.diagnosis.process-list', processParams($setup, $main)));

    expect(Process::query()->where('name', 'Vezetői kör')->sole()->source)->toBe(ProcessSource::Conversation)
        ->and(Process::query()->where('name', 'döntés négyszemközt')->sole()->unclear)->toBeTrue()
        ->and($conversation->refresh()->completed_at)->not->toBeNull();
});

it('marks a process named in the fourth answer as a breakpoint candidate', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    $conversation = app(ConversationService::class)->forUser($setup['organization'], $setup['leader'], $main);
    $conversation->update(['answers' => [4 => 'Az Ops heti után.']]);
    $data = ['processes' => [['name' => 'Ops heti', 'kind' => 'process', 'rhythm' => null, 'rhythm_label' => null, 'headcount' => null]]];
    AiGateway::fake([new StructuredTextResponse($data, json_encode($data), new TextUsage(10, 5), new Meta('anthropic', 'claude-test'))]);

    app(ConversationService::class)->extract($conversation->refresh(), 4);
    app(ConversationService::class)->finish($conversation->refresh());

    expect(Process::query()->sole()->breakpoint_candidate)->toBeTrue();
});

it('needs at least one answer to go on', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);

    Livewire::test(ProcessConversation::class, processParams($setup, $main))
        ->call('finish')
        ->assertHasErrors(['answers']);
});

it('connects an iCal link from V6·b and lands on the detected list', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);
    Http::fake(['calendar.example.com/*' => Http::response("BEGIN:VCALENDAR\r\nVERSION:2.0\r\nEND:VCALENDAR")]);

    Livewire::test(ProcessCalendar::class, processParams($setup, $main))
        ->assertSee('Mit olvasunk, és mit soha.')
        ->set('choosing', true)
        ->set('icsUrl', 'https://calendar.example.com/secret.ics')
        ->call('connectUrl')
        ->assertRedirect(route('client.diagnosis.process-list', processParams($setup, $main)));

    expect(CalendarConnection::query()->sole()->provider)->toBe(CalendarConnection::ICS_URL);
});

it('says "Most nem sikerült" and offers the conversation when the calendar cannot be read', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);
    Http::fake(['calendar.example.com/*' => Http::response('', 500)]);

    Livewire::test(ProcessCalendar::class, processParams($setup, $main))
        ->set('choosing', true)
        ->set('icsUrl', 'https://calendar.example.com/secret.ics')
        ->call('connectUrl')
        ->assertSee('Most nem sikerült.')
        ->assertSee('Mondd el inkább');
});

it('connects Google through OAuth, read-only', function () {
    config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);
    Socialite::fake('google', (new SocialiteUser)->map(['id' => '1'])->setToken('token')->setRefreshToken('refresh')->setExpiresIn(3600));
    Http::fake(['www.googleapis.com/*' => Http::response(['items' => []])]);

    $this->get(route('client.diagnosis.process-calendar.connect', [...processParams($setup, $main), 'provider' => 'google']))
        ->assertRedirect('https://socialite.fake/google/authorize');

    $this->get(route('client.calendar.callback', 'google'))
        ->assertRedirect(route('client.diagnosis.process-list', processParams($setup, $main)));

    $connection = CalendarConnection::query()->sole();
    expect($connection->provider)->toBe('google')
        ->and($connection->credentials['refresh_token'])->toBe('refresh');
});

it('explains the IT admin approval of a company Microsoft account', function () {
    config(['services.microsoft.client_id' => 'id', 'services.microsoft.client_secret' => 'secret']);
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);
    Socialite::fake('microsoft');

    $this->get(route('client.diagnosis.process-calendar.connect', [...processParams($setup, $main), 'provider' => 'microsoft']));
    $this->get(route('client.calendar.callback', ['provider' => 'microsoft', 'error' => 'access_denied', 'error_description' => 'AADSTS65001: consent required']))
        ->assertRedirect(route('client.diagnosis.process-calendar', processParams($setup, $main)))
        ->assertSessionHas('calendar_error', 'A céged IT-adminjának jóvá kell hagynia a mégis hozzáférését. Addig beszélgessünk.');
});

it('does not offer an OAuth provider without keys', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);

    $this->get(route('client.diagnosis.process-calendar.connect', [...processParams($setup, $main), 'provider' => 'google']))->assertNotFound();
});

it('lets the admin edit processes, review uncertain ones and write the recommendation', function () {
    $setup = diagnosisTeam();
    $process = Process::factory()->for($setup['organization'])->fromCalendar(ProcessConfidence::Medium)->create(['name' => 'Egyeztetés']);
    actingAsAdmin($setup['admin']);

    Livewire::test(AdminProcesses::class, ['organization' => $setup['organization']])
        ->assertSee('Átnézésre: bizonytalan és egyéb')
        ->assertSee('Egyeztetés')
        ->call('startEdit', $process->id)
        ->set('edit.name', 'Heti egyeztetés')
        ->set('edit.accepted', true)
        ->call('saveEdit')
        ->assertHasNoErrors()
        ->set('recommendation', 'A heti egyeztetés legyen benne.')
        ->call('saveRecommendation');

    expect($process->refresh()->name)->toBe('Heti egyeztetés')
        ->and($process->accepted_at)->not->toBeNull()
        ->and($process->renames()->sole()->created_by)->toBe($setup['admin']->id)
        ->and($setup['organization']->refresh()->process_recommendation)->toBe('A heti egyeztetés legyen benne.');
});
