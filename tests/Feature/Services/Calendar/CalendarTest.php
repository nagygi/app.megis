<?php

use App\Enums\ProcessKind;
use App\Enums\ProcessSource;
use App\Models\AiCall;
use App\Models\CalendarConnection;
use App\Models\Process;
use App\Services\Ai\AiGateway;
use App\Services\Calendar\CalendarDetector;
use App\Services\Calendar\CalendarException;
use App\Services\Calendar\CalendarService;
use App\Services\Calendar\IcsParser;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\StructuredTextResponse;

function namingAnswer(array $suggestions): StructuredTextResponse
{
    $data = ['suggestions' => $suggestions];

    return new StructuredTextResponse($data, json_encode($data), new TextUsage(20, 10), new Meta('anthropic', 'claude-test'));
}

it('reads an iCal file without private, cancelled, all-day and declined events', function () {
    $events = app(IcsParser::class)->parse(sampleIcs('anna@example.com', 'bence@example.com'), now()->subDays(90), now(), 'anna@example.com');

    $titles = collect($events)->pluck('title')->unique()->values()->all();

    expect($titles)->toEqualCanonicalizing(['Vezetői kör', 'Céges buli'])
        ->and(collect($events)->where('title', 'Vezetői kör')->count())->toBeGreaterThanOrEqual(11)
        ->and(collect($events)->firstWhere('title', 'Vezetői kör')->seriesKey)->toBe('vezetoi-kor');
});

it('turns the iCal link into processes with participants and occasions', function () {
    $setup = diagnosisTeam(peers: 1);
    $main = startMainDiagnosis($setup);
    $member = $setup['peers'][0];
    Http::fake(['calendar.example.com/*' => Http::response(sampleIcs($setup['leader']->email, $member->email))]);
    AiGateway::fake([namingAnswer([['ref' => 'P1', 'name' => 'Vezetői kör', 'kind' => 'routine', 'parent_ref' => null, 'merge_with' => null]])]);

    $connection = app(CalendarService::class)->connectIcsUrl($setup['leader'], $setup['organization'], $main, 'https://calendar.example.com/secret.ics');

    $process = Process::query()->sole();
    expect($process->source)->toBe(ProcessSource::Calendar)
        ->and($process->rhythm_label)->toStartWith('keddenként')
        ->and($process->headcount)->toBe(3)
        ->and($process->accepted_at)->toBeNull()
        ->and($process->occasions()->count())->toBeGreaterThanOrEqual(11)
        ->and($process->participants()->whereNotNull('user_id')->pluck('user_id')->all())->toEqualCanonicalizing([$setup['leader']->id, $member->id])
        ->and($process->participants()->whereNull('user_id')->value('name'))->toBe('Külsős Kata')
        ->and($connection->one_off_count)->toBe(1)
        ->and($connection->credentials['url'])->toBe('https://calendar.example.com/secret.ics')
        ->and(AiCall::query()->sole()->task)->toBe('calendar_naming');

    // The secret address is stored encrypted.
    expect(DB::table('calendar_connections')->value('credentials'))->not->toContain('secret.ics');
});

it('never sends names or e-mail addresses to the AI', function () {
    $setup = diagnosisTeam(peers: 1);
    $main = startMainDiagnosis($setup);
    Http::fake(['calendar.example.com/*' => Http::response(sampleIcs($setup['leader']->email, $setup['peers'][0]->email))]);
    $prompts = [];
    AiGateway::fake(function (string $prompt) use (&$prompts) {
        $prompts[] = $prompt;

        return namingAnswer([]);
    });

    app(CalendarService::class)->connectIcsUrl($setup['leader'], $setup['organization'], $main, 'https://calendar.example.com/secret.ics');

    expect(implode("\n", $prompts))->not->toContain($setup['leader']->email)
        ->not->toContain('Kata')
        ->toContain('Vezetői kör');
});

it('keeps the titles when the AI naming fails', function () {
    $setup = diagnosisTeam(peers: 1);
    $main = startMainDiagnosis($setup);
    Http::fake(['calendar.example.com/*' => Http::response(sampleIcs($setup['leader']->email, $setup['peers'][0]->email))]);
    AiGateway::fake(fn () => throw new RuntimeException('down'));

    app(CalendarService::class)->connectIcsUrl($setup['leader'], $setup['organization'], $main, 'https://calendar.example.com/secret.ics');

    expect(Process::query()->sole()->name)->toBe('Vezetői kör');
});

it('reconnects without duplicating the processes', function () {
    $setup = diagnosisTeam(peers: 1);
    $main = startMainDiagnosis($setup);
    Http::fake(['calendar.example.com/*' => Http::response(sampleIcs($setup['leader']->email, $setup['peers'][0]->email))]);
    AiGateway::fake([namingAnswer([]), namingAnswer([])]);
    $calendars = app(CalendarService::class);

    $first = $calendars->connectIcsUrl($setup['leader'], $setup['organization'], $main, 'https://calendar.example.com/secret.ics');
    $calendars->connectIcsUrl($setup['leader'], $setup['organization'], $main, 'https://calendar.example.com/secret.ics');

    expect(Process::query()->count())->toBe(1)
        ->and($first->refresh()->status)->toBe(CalendarConnection::DISCONNECTED);
});

it('disconnects: the metadata goes at once, the process and the occasion dates stay', function () {
    $setup = diagnosisTeam(peers: 1);
    $main = startMainDiagnosis($setup);
    Http::fake(['calendar.example.com/*' => Http::response(sampleIcs($setup['leader']->email, $setup['peers'][0]->email))]);
    AiGateway::fake([namingAnswer([])]);
    $calendars = app(CalendarService::class);
    $connection = $calendars->connectIcsUrl($setup['leader'], $setup['organization'], $main, 'https://calendar.example.com/secret.ics');
    $occasions = Process::query()->sole()->occasions()->count();

    $calendars->disconnect($connection);

    expect($connection->refresh()->status)->toBe(CalendarConnection::DISCONNECTED)
        ->and($connection->credentials)->toBeNull()
        ->and($connection->events()->count())->toBe(0)
        ->and(Process::query()->sole()->occasions()->count())->toBe($occasions)
        ->and(Process::query()->sole()->occasions()->whereNotNull('calendar_event_id')->count())->toBe(0);
});

it('deletes the metadata 90 days after connecting', function () {
    $setup = diagnosisTeam(peers: 1);
    $main = startMainDiagnosis($setup);
    Http::fake(['calendar.example.com/*' => Http::response(sampleIcs($setup['leader']->email, $setup['peers'][0]->email))]);
    AiGateway::fake([namingAnswer([])]);
    $connection = app(CalendarService::class)->connectIcsUrl($setup['leader'], $setup['organization'], $main, 'https://calendar.example.com/secret.ics');

    $this->travel(91)->days();
    $this->artisan('megis:calendar:prune')->assertSuccessful();

    expect($connection->events()->count())->toBe(0)
        ->and(Process::query()->sole()->occasions()->count())->toBeGreaterThan(0);
});

it('adds the new occasions of an accepted process on the daily refresh', function () {
    $setup = diagnosisTeam(peers: 1);
    $main = startMainDiagnosis($setup);
    Http::fake(['calendar.example.com/*' => Http::response(sampleIcs($setup['leader']->email, $setup['peers'][0]->email))]);
    AiGateway::fake([namingAnswer([])]);
    $connection = app(CalendarService::class)->connectIcsUrl($setup['leader'], $setup['organization'], $main, 'https://calendar.example.com/secret.ics');
    $process = Process::query()->sole();
    $process->update(['accepted_at' => now()]);
    $process->occasions()->orderByDesc('occurred_at')->first()->delete();
    $before = $process->occasions()->count();

    $this->artisan('megis:calendar:sync')->assertSuccessful();

    expect($process->occasions()->count())->toBe($before + 1)
        ->and($connection->refresh()->last_synced_at)->not->toBeNull();
});

it('disconnects automatically when the time window ended', function () {
    $setup = diagnosisTeam(peers: 1);
    $main = startMainDiagnosis($setup);
    Http::fake(['calendar.example.com/*' => Http::response(sampleIcs($setup['leader']->email, $setup['peers'][0]->email))]);
    AiGateway::fake([namingAnswer([])]);
    $connection = app(CalendarService::class)->connectIcsUrl($setup['leader'], $setup['organization'], $main, 'https://calendar.example.com/secret.ics');
    $main->update(['publishes_on' => today()->subDay()]);

    $this->artisan('megis:calendar:sync')->assertSuccessful();

    expect($connection->refresh()->status)->toBe(CalendarConnection::DISCONNECTED);
});

it('marks the connection broken when the access was withdrawn', function () {
    $setup = diagnosisTeam(peers: 1);
    $main = startMainDiagnosis($setup);
    Http::fake(['calendar.example.com/*' => Http::sequence()
        ->push(sampleIcs($setup['leader']->email, $setup['peers'][0]->email))
        ->push('', 404)]);
    AiGateway::fake([namingAnswer([])]);
    $connection = app(CalendarService::class)->connectIcsUrl($setup['leader'], $setup['organization'], $main, 'https://calendar.example.com/secret.ics');

    $this->artisan('megis:calendar:sync')->assertSuccessful();

    expect($connection->refresh()->status)->toBe(CalendarConnection::BROKEN);
});

it('refuses an address that is not https or webcal', function () {
    $setup = diagnosisTeam();

    app(CalendarService::class)->connectIcsUrl($setup['leader'], $setup['organization'], null, 'http://calendar.example.com/x.ics');
})->throws(CalendarException::class);

it('applies the suggested names, one-off types and subprocesses', function () {
    $setup = diagnosisTeam(peers: 1);
    $main = startMainDiagnosis($setup);
    $start = CarbonImmutable::now()->subWeeks(10)->startOfWeek()->setTime(9, 0);
    $rows = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//t//HU'];
    foreach (['ops' => 'Ops heti', 'hibak' => 'Hibák átbeszélése', 'buli' => 'Péntek esti sör'] as $uid => $title) {
        $rows = [...$rows, 'BEGIN:VEVENT', 'UID:'.$uid, 'DTSTART:'.$start->utc()->format('Ymd\THis\Z'), 'DTEND:'.$start->addHour()->utc()->format('Ymd\THis\Z'),
            'RRULE:FREQ=WEEKLY;COUNT=8', 'SUMMARY:'.$title, 'END:VEVENT'];
    }
    Http::fake(['calendar.example.com/*' => Http::response(implode("\r\n", [...$rows, 'END:VCALENDAR']))]);
    AiGateway::fake([namingAnswer([
        ['ref' => 'P1', 'name' => 'Ops heti', 'kind' => 'routine', 'parent_ref' => null, 'merge_with' => null],
        ['ref' => 'P2', 'name' => 'Hibák átbeszélése', 'kind' => 'routine', 'parent_ref' => 'P1', 'merge_with' => null],
        ['ref' => 'P3', 'name' => 'Péntek esti sör', 'kind' => 'one_off', 'parent_ref' => null, 'merge_with' => null],
    ])]);

    app(CalendarService::class)->connectIcsUrl($setup['leader'], $setup['organization'], $main, 'https://calendar.example.com/secret.ics');

    $ops = Process::query()->where('name', 'Ops heti')->sole();
    expect(Process::query()->where('name', 'Hibák átbeszélése')->sole()->parent_id)->toBe($ops->id)
        ->and(Process::query()->where('name', 'Péntek esti sör')->sole()->kind)->toBe(ProcessKind::OneOff);
});

it('offers to merge a calendar pattern into a process from the conversation', function () {
    $setup = diagnosisTeam(peers: 1);
    $main = startMainDiagnosis($setup);
    $conversationProcess = Process::factory()->for($setup['organization'])->create([
        'name' => 'Keddi kör', 'source' => ProcessSource::Conversation, 'confidence' => 'conversation',
    ]);
    Http::fake(['calendar.example.com/*' => Http::response(sampleIcs($setup['leader']->email, $setup['peers'][0]->email))]);
    AiGateway::fake([namingAnswer([['ref' => 'P1', 'name' => 'Vezetői kör', 'kind' => 'routine', 'parent_ref' => null, 'merge_with' => 'C1']])]);

    app(CalendarService::class)->connectIcsUrl($setup['leader'], $setup['organization'], $main, 'https://calendar.example.com/secret.ics');

    $calendarProcess = Process::query()->where('source', ProcessSource::Calendar)->sole();
    expect($conversationProcess->refresh()->merge_candidate_id)->toBe($calendarProcess->id);

    app(CalendarDetector::class)->merge($calendarProcess, $conversationProcess);

    expect(Process::query()->count())->toBe(1)
        ->and($conversationProcess->refresh()->calendar_series_key)->toBe('series:vezetoi-kor')
        ->and($conversationProcess->occasions()->count())->toBeGreaterThan(0);
});
