<?php

use App\Enums\DiagnosisLevel;
use App\Enums\DiagnosisRowKind;
use App\Enums\MainDiagnosisStage;
use App\Enums\MainDiagnosisSubject;
use App\Enums\MaterialContext;
use App\Enums\MaterialStatus;
use App\Enums\ParticipantRole;
use App\Enums\ScoreResultState;
use App\Enums\StepMark;
use App\Models\Consent;
use App\Models\Diagnosis;
use App\Models\EvidenceTrace;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\OrganizationValueSet;
use App\Models\PersonalValueSet;
use App\Models\Process;
use App\Models\ProcessOccasion;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;
use App\Services\Auth\GuardSession;
use App\Services\Diagnoses\DiagnosisCalculator;
use App\Services\Diagnoses\ReadingService;
use App\Services\MainDiagnoses\ExperienceService;
use App\Services\MainDiagnoses\MainDiagnosisService;
use App\Services\MainDiagnoses\StageMachine;
use App\Services\Organizations\OrganizationValueSetService;
use App\Services\Report\PublishService;
use App\Services\Values\PersonalValueSetService;
use App\Services\Values\ValueService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature');

// Browser tests load the built assets, so layout and contrast are checked for real (npm run build first).
pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->beforeEach(fn () => $this->withVite())
    ->in('Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Logs an admin in on the admin guard the way a real login does: the session
 * carries the current auth epoch and a fresh activity time.
 */
function actingAsAdmin(?User $admin = null): User
{
    $admin ??= User::factory()->admin()->create();

    test()->actingAs($admin, GuardSession::ADMIN)->withSession([
        'auth_epoch.'.GuardSession::ADMIN => $admin->auth_epoch,
        GuardSession::ADMIN_LAST_ACTIVITY_KEY => now()->getTimestamp(),
    ]);

    return $admin;
}

/**
 * Logs a customer in on the customer guard, with the session's auth epoch.
 */
function actingAsCustomer(User $customer): User
{
    test()->actingAs($customer, GuardSession::WEB)->withSession([
        'auth_epoch.'.GuardSession::WEB => $customer->auth_epoch,
    ]);

    return $customer;
}

/**
 * An activated customer with an active membership in an active organization.
 */
function customerOf(?Organization $organization = null): User
{
    $organization ??= Organization::factory()->create();
    $customer = User::factory()->create();

    Membership::factory()->for($organization)->for($customer)->create();

    return $customer;
}

/**
 * One of the starting products (02 · 2.1) with its diagnosis flow (17 · 17.3).
 */
function startingProduct(string $key = Product::FULL): Product
{
    return Product::query()->where('key', $key)->first() ?? Product::factory()->starting($key)->create();
}

/**
 * The organization's value set with 3 values in its own words and the brand
 * base, saved as a version (08).
 */
function organizationValues(Organization $organization, int $count = 3, bool $withBrand = true): OrganizationValueSet
{
    $service = app(OrganizationValueSetService::class);
    $set = $service->forOrganization($organization);
    $items = collect(range(1, $count))->map(function (int $index) use ($service) {
        $value = app(ValueService::class)->create([
            'name' => 'Érték '.$index.' '.Str::random(4),
            'meanings' => ['Jelentés '.$index],
            'low_examples' => ['Alig '.$index],
            'high_examples' => ['Nagyon '.$index],
        ]);

        return $service->itemFromValue($value->load('latestVersion'));
    })->all();

    $service->save($set, $items, $withBrand
        ? ['purpose' => 'Miért', 'value_proposition' => 'Mit kap', 'impact' => 'Mi marad']
        : []);

    return $set->refresh();
}

/**
 * A team of an organization with the given product: a leader (by default the
 * organization's leader too), peers, a contact admin and the value set (17 · 17.2).
 *
 * @return array{organization: Organization, team: Team, leader: User, peers: list<User>, admin: User}
 */
function diagnosisTeam(string $product = Product::FULL, int $peers = 3, bool $organizationLeader = true, bool $withValues = true): array
{
    $admin = User::factory()->admin()->create();
    $organization = Organization::factory()->create([
        'product_id' => startingProduct($product)->id,
        'contact_admin_id' => $admin->id,
    ]);
    $team = Team::factory()->for($organization)->create();
    $leader = User::factory()->create();
    Membership::factory()->for($organization)->for($leader)->create(['is_organization_leader' => $organizationLeader]);
    $team->users()->attach($leader->id, ['is_leader' => true]);

    $peerUsers = collect(range(1, $peers))->map(function () use ($organization, $team) {
        $peer = User::factory()->create();
        Membership::factory()->for($organization)->for($peer)->create();
        $team->users()->attach($peer->id, ['is_leader' => false]);

        return $peer;
    })->all();

    if ($withValues) {
        organizationValues($organization);
    }

    return ['organization' => $organization, 'team' => $team, 'leader' => $leader, 'peers' => $peerUsers, 'admin' => $admin];
}

/**
 * A main diagnosis of the team, invited unless asked otherwise (17 · 17.2).
 *
 * @param  array{organization: Organization, team: Team, leader: User, admin: User}  $setup
 * @param  array<string, mixed>  $overrides
 */
function startMainDiagnosis(array $setup, bool $invite = true, array $overrides = []): MainDiagnosis
{
    Mail::fake();

    $service = app(MainDiagnosisService::class);
    $main = $service->create([
        'organization_id' => $setup['organization']->id,
        'subject' => MainDiagnosisSubject::Team->value,
        'team_id' => $setup['team']?->id,
        'leader_id' => $setup['leader']->id,
        'starts_on' => today()->addWeek()->toDateString(),
        'publishes_on' => today()->addWeeks(6)->toDateString(),
        'show_peer_quotes' => true,
        ...$overrides,
    ], $setup['admin']);

    if ($invite) {
        $service->invite($main, $setup['admin']);
    }

    return $main->refresh();
}

/**
 * The leader's own values, confirmed with "Tovább" (24).
 */
function confirmOwnValues(User $user, int $count = 1): PersonalValueSet
{
    $service = app(PersonalValueSetService::class);
    $set = $service->forUser($user);
    $items = collect(range(1, $count))->map(fn (int $index) => [
        'value_id' => null,
        'name' => 'Saját '.$index.' '.Str::random(4),
        'meaning' => 'Ezt jelenti '.$index,
        'low_example' => 'Alig',
        'high_example' => 'Nagyon',
        'source' => 'new',
    ])->all();

    $service->save($set, $items, recordVersion: true);

    return $set->refresh();
}

/**
 * A team diagnosis the leader started: it runs and the peers are invited (23 · 23.4).
 *
 * @return array{0: array<string, mixed>, 1: MainDiagnosis}
 */
function runningDiagnosis(bool $showQuotes = true): array
{
    $setup = diagnosisTeam(peers: 2);
    $main = startMainDiagnosis($setup, overrides: ['show_peer_quotes' => $showQuotes]);
    confirmOwnValues($setup['leader']);
    $version = app(OrganizationValueSetService::class)->forOrganization($setup['organization'])->latestVersion;
    $rows = app(ExperienceService::class)->rows($version->snapshot);
    app(ExperienceService::class)->submit($main, $setup['leader'], $version, collect($rows)->mapWithKeys(fn ($row) => [$row['key'] => '4'])->all());
    $main->stepMarks()->create(['user_id' => $setup['leader']->id, 'step' => 'processes', 'mark' => StepMark::Skipped]);
    // Képességek counts only while the capability measurement is on (NM-12).
    $main->stepMarks()->create(['user_id' => $setup['leader']->id, 'step' => 'capabilities', 'mark' => StepMark::Skipped]);
    app(MainDiagnosisService::class)->start($main->refresh(), $setup['leader']);

    return [$setup, $main->refresh()];
}

function peerParams(array $setup, $main): array
{
    return ['organization' => $setup['organization'], 'diagnosis' => $main->diagnosis];
}

/**
 * A processed material of the diagnosis with its scores per value: value
 * position => score, or null for "nincs adat".
 *
 * @param  array<int, ?float>  $scores
 */
function scoredMaterial(MainDiagnosis $main, ?ProcessOccasion $occasion, array $scores, ?User $user = null): Material
{
    $material = Material::factory()->create([
        'context' => $occasion === null ? MaterialContext::Mirror : MaterialContext::Occasion,
        'status' => MaterialStatus::Processed,
        'parent_diagnosis_id' => $main->diagnosis_id,
        'organization_id' => $main->diagnosis->organization_id,
        'process_occasion_id' => $occasion?->id,
        'user_id' => $user?->id,
        'placed_at' => now(),
    ]);
    $values = $main->valueSetVersion->snapshot['values'];

    foreach ($scores as $position => $score) {
        $material->scores()->create([
            'value_id' => $values[$position]['value_id'],
            'state' => $score === null ? ScoreResultState::NoData : ScoreResultState::Scored,
            'score' => $score,
            'detection_ids' => [],
        ]);
    }

    return $material;
}

/**
 * @return array{0: array<string, mixed>, 1: MainDiagnosis, 2: Process}
 */
function calculatedDiagnosis(string $product = Product::FULL): array
{
    [$setup, $main] = runningDiagnosis();

    if ($product !== Product::FULL) {
        $main->update(['product_key' => $product]);
    }

    $process = Process::factory()->accepted()->for($setup['organization'])->create(['name' => 'Retró']);
    $main->processes()->sync([$process->id]);

    return [$setup, $main->refresh(), $process];
}

function occasionOn(Process $process, int $daysAgo): ProcessOccasion
{
    return ProcessOccasion::create(['process_id' => $process->id, 'occurred_at' => now()->subDays($daysAgo), 'source' => 'manual']);
}

function processDiagnosisOf(MainDiagnosis $main, Process $process): Diagnosis
{
    return Diagnosis::query()->where('parent_id', $main->diagnosis_id)->where('level', DiagnosisLevel::Process)->where('process_id', $process->id)->sole();
}

/**
 * A Teljes diagnosis in reading with everything approved: a quote on the
 * first value, two occasions of the process, and the first peer's T4.
 *
 * @return array{0: array<string, mixed>, 1: MainDiagnosis}
 */
function approvedReading(): array
{
    [$setup, $main, $process] = calculatedDiagnosis();
    $peer = $setup['peers'][0];
    $main->participantFor($peer, ParticipantRole::Peer)->update(['started_at' => now()]);
    Consent::create(['user_id' => $peer->id, 'main_diagnosis_id' => $main->id, 'kind' => 'answers', 'text_version' => 'v', 'text' => 't', 'given_at' => now()]);
    app(ExperienceService::class)->submit($main, $peer, $main->valueSetVersion, collect(app(ExperienceService::class)->rows($main->valueSetVersion->snapshot))->mapWithKeys(fn ($row) => [$row['key'] => '3'])->all());

    $material = scoredMaterial($main, null, [0 => 4, 1 => 2, 2 => 5], $setup['leader']);
    $detection = $material->detections()->create(['quote' => 'Szóltam a csúszásról, mielőtt kérdezték volna.', 'free_name' => 'Őszinteség', 'strength' => 3]);
    $material->scores()->where('value_id', $main->valueSetVersion->snapshot['values'][0]['value_id'])->update(['detection_ids' => json_encode([$detection->id])]);
    scoredMaterial($main, occasionOn($process, 5), [0 => 3, 1 => 3, 2 => 3]);
    scoredMaterial($main, occasionOn($process, 2), [0 => 5, 1 => 5, 2 => 5]);
    app(DiagnosisCalculator::class)->recalculate($main);
    app(StageMachine::class)->transition($main->refresh(), MainDiagnosisStage::Reading, $setup['admin']);
    $main->update(['threshold_overridden_at' => now(), 'threshold_overridden_by' => $setup['admin']->id]);

    $reading = app(ReadingService::class);
    $admin = $setup['admin'];
    $trace = EvidenceTrace::query()->firstOrFail();
    $reading->approveClaim($reading->saveClaim($main, null, ['diagnosis_row_id' => $trace->diagnosis_row_id, 'kind' => 'strength', 'text' => 'Nálatok szólnak a csúszásról.', 'trace_ids' => [$trace->id]], $admin), $admin);

    foreach ($main->diagnosis->rows()->with('diagnosis')->get() as $row) {
        in_array($row->kind, [DiagnosisRowKind::Area, DiagnosisRowKind::TreeElement], true) ? $reading->scoreRow($row, '3', $admin) : $reading->approveRow($row, $admin);
    }

    foreach ($reading->requiredTexts($main) as $key) {
        $reading->approveText($reading->saveText($main, $key, 'A '.$key.' mondata.', $key === 'recommendation' ? ['row_id' => $trace->diagnosis_row_id, 'from' => 2, 'to' => 3] : null, $admin), $admin);
    }

    $reading->approveGap($main->refresh(), null, $admin);
    app(DiagnosisCalculator::class)->recalculate($main->refresh());

    return [$setup, $main->refresh()];
}

/**
 * The approved reading, published (21 · 21.3).
 *
 * @return array{0: array<string, mixed>, 1: MainDiagnosis}
 */
function publishedReport(): array
{
    [$setup, $main] = approvedReading();
    app(PublishService::class)->publish($main, $setup['admin']);

    return [$setup, $main->refresh()];
}

/**
 * Cloudflare's verification answers with the given result.
 */
function fakeTurnstile(bool $success = true): void
{
    Http::fake([
        'https://challenges.cloudflare.com/*' => Http::response(['success' => $success]),
        'https://api.pwnedpasswords.com/*' => Http::response(''),
    ]);
}

/**
 * Browser tests: fills the field the Turnstile widget would add, so the flow
 * does not depend on Cloudflare being reachable; the server check is faked.
 */
function passTurnstile(mixed $page): void
{
    $page->script(<<<'JS'
        () => document.querySelector('form').insertAdjacentHTML(
            'beforeend',
            '<input type="hidden" name="cf-turnstile-response" value="test-token">'
        )
    JS);
}

/**
 * Logs the customer in with the password, as on B1 (14 · 14.2).
 */
function loginInBrowser($user): mixed
{
    fakeTurnstile();
    $user->forceFill(['password' => Hash::make('a-long-enough-password')])->save();

    $page = visit('/belepes');
    $page->click('Jelszóval lépek be');
    $page->fill('email', $user->email)->fill('password', 'a-long-enough-password');
    passTurnstile($page);
    $page->press('Belépek');

    return $page;
}

/**
 * Logs an admin in with the password (01 · 1.2).
 */
function adminLoginInBrowser(User $admin): mixed
{
    fakeTurnstile();
    $admin->forceFill(['password' => Hash::make('a-long-enough-password')])->save();

    $page = visit('/admin/belepes');
    $page->fill('email', $admin->email)->fill('password', 'a-long-enough-password');
    passTurnstile($page);
    $page->press('Belépek');

    return $page;
}

/**
 * A synthetic export of the mégis tár: sheet name => rows, the first row is the header.
 *
 * @param  array<string, list<list<string>>>  $sheets
 */
function capabilitySpreadsheet(array $sheets): string
{
    $path = tempnam(sys_get_temp_dir(), 'kepessegtar').'.xlsx';
    $writer = new Writer;
    $writer->openToFile($path);
    $first = true;

    foreach ($sheets as $name => $rows) {
        $sheet = $first ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName($name);
        $first = false;

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
    }

    $writer->close();

    return $path;
}

/**
 * A weekly "Vezetői kör" for 12 weeks with the leader and two others, plus
 * events that must never be read (09 · 9.1, 9.3).
 */
function sampleIcs(string $leaderEmail, string $memberEmail): string
{
    $start = CarbonImmutable::now('UTC')->subWeeks(12)->next('Tuesday')->setTime(7, 0);
    $until = CarbonImmutable::now('UTC')->subDay();
    $stamp = $start->format('Ymd\THis\Z');
    $end = $start->addMinutes(90)->format('Ymd\THis\Z');
    $untilStamp = $until->format('Ymd\THis\Z');
    $past = CarbonImmutable::now('UTC')->subWeeks(2)->setTime(10, 0)->format('Ymd\THis\Z');
    $pastEnd = CarbonImmutable::now('UTC')->subWeeks(2)->setTime(11, 0)->format('Ymd\THis\Z');
    $day = CarbonImmutable::now('UTC')->subWeeks(3)->format('Ymd');

    return implode("\r\n", [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//mégis//teszt//HU',
        'BEGIN:VEVENT',
        'UID:vezetoi-kor',
        'DTSTART:'.$stamp,
        'DTEND:'.$end,
        'RRULE:FREQ=WEEKLY;UNTIL='.$untilStamp,
        'SUMMARY:Vezetői kör',
        'DESCRIPTION:Heti áttekintés',
        'ATTENDEE;CN=Anna:mailto:'.$leaderEmail,
        'ATTENDEE;CN=Bence:mailto:'.$memberEmail,
        'ATTENDEE;CN=Külsős Kata:mailto:kata@partner.example',
        'END:VEVENT',
        'BEGIN:VEVENT',
        'UID:magan',
        'DTSTART:'.$past,
        'DTEND:'.$pastEnd,
        'CLASS:PRIVATE',
        'SUMMARY:Orvos',
        'END:VEVENT',
        'BEGIN:VEVENT',
        'UID:lemondva',
        'DTSTART:'.$past,
        'DTEND:'.$pastEnd,
        'STATUS:CANCELLED',
        'SUMMARY:Elmaradt kör',
        'END:VEVENT',
        'BEGIN:VEVENT',
        'UID:egesz-nap',
        'DTSTART;VALUE=DATE:'.$day,
        'SUMMARY:Csapatnap',
        'END:VEVENT',
        'BEGIN:VEVENT',
        'UID:elutasitva',
        'DTSTART:'.$past,
        'DTEND:'.$pastEnd,
        'SUMMARY:Más meetingje',
        'ATTENDEE;PARTSTAT=DECLINED:mailto:'.$leaderEmail,
        'END:VEVENT',
        'BEGIN:VEVENT',
        'UID:egyszeri',
        'DTSTART:'.$past,
        'DTEND:'.$pastEnd,
        'SUMMARY:Céges buli',
        'END:VEVENT',
        'END:VCALENDAR',
    ]);
}
