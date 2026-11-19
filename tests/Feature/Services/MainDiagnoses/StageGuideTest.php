<?php

use App\Enums\MainDiagnosisStage;
use App\Enums\ParticipantRole;
use App\Models\DiagnosisClaim;
use App\Models\User;
use App\Services\MainDiagnoses\MainDiagnosisService;
use App\Services\MainDiagnoses\StageGuide;
use App\Services\MainDiagnoses\StepProgress;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    Mail::fake();
});

/**
 * @param  array{issues: list<array{text: string}>}  $guide
 * @return list<string>
 */
function issueTexts(array $guide): array
{
    return array_column($guide['issues'], 'text');
}

it('asks the admin to send the invitation of a draft, on the Beállítás tab', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup, invite: false);

    $guide = app(StageGuide::class)->next($main, $setup['admin']);

    expect($guide['turn'])->toBe('admin')
        ->and($guide['issues'])->toBe([])
        ->and($guide['cta'])->toBe(['label' => 'Elküldöm a meghívót', 'href' => route('admin.main-diagnoses.edit', $main->diagnosis).'#invitation']);
});

it('lists what blocks the invitation with the place to fix it, and offers no button', function () {
    $setup = diagnosisTeam(organizationLeader: false, withValues: false);
    $main = startMainDiagnosis($setup, invite: false);
    $setup['organization']->update(['contact_admin_id' => null]);

    $guide = app(StageGuide::class)->next($main->refresh(), $setup['admin']);

    expect($guide['cta'])->toBeNull()
        ->and($guide['issues'])->toBe([
            ['tone' => 'error', 'text' => __('admin.main_diagnoses.blocker_contact'), 'href' => route('admin.main-diagnoses.edit', $main->diagnosis).'#contact_admin_id'],
            ['tone' => 'error', 'text' => __('admin.main_diagnoses.blocker_values'), 'href' => route('admin.organizations.values', $setup['organization'])],
        ]);
});

it('waits for the leader after the invitation, and warns when the invitation and the deadline are overdue', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup, overrides: ['starts_on' => today()->addDays(4)->toDateString()]);
    $guide = app(StageGuide::class);

    expect($guide->next($main, $setup['admin']))
        ->turn->toBe('leader')
        ->body->toContain($setup['leader']->name.' meghívót kapott ('.$main->invited_at->format('Y.m.d.').'), még nem lépett be.')
        ->issues->toBe([])
        ->cta->toBe(['label' => 'Emlékeztetőt küldök', 'href' => route('admin.main-diagnoses.run', $main->diagnosis).'#leader']);

    $this->travel(6)->days();

    expect(issueTexts($guide->next($main->refresh(), $setup['admin'])))->toBe([
        'A meghívó 6 napja ment ki, és a vezető még nem lépett be. A Beérkezőben újraküldheted.',
        'A vezető határideje ('.$main->starts_on->format('Y.m.d.').') 2 napja lejárt. Magától nem történik semmi: emlékeztesd, vagy adj új határidőt a Beállítás fülön.',
    ]);
});

it('counts the leader steps while the leader fills them in', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    app(MainDiagnosisService::class)->opened($main, $main->participants()->where('role', ParticipantRole::Leader)->sole());

    $steps = app(StepProgress::class)->forLeader($main->refresh(), $setup['leader']);

    $guide = app(StageGuide::class)->next($main, $setup['admin']);

    expect($guide['turn'])->toBe('leader')
        ->and($guide['body'])->toStartWith($setup['leader']->name.' kitölti a lépéseit.')
        ->and($steps->doneCount())->toBeLessThan($steps->count())
        ->and($guide['progress'])->toBe(['done' => $steps->doneCount(), 'total' => $steps->count(), 'label' => $steps->doneCount().' / '.$steps->count().' lépés kész']);
});

it('sends an admin who cannot open the Futás tab to the contact instead of linking it', function () {
    [$setup, $main] = runningDiagnosis();

    $guide = app(StageGuide::class)->next($main, User::factory()->admin()->create());

    expect($guide['cta'])->toBeNull()
        ->and($guide['note'])->toBe('Az olvasatot a kapcsolattartó ('.$setup['admin']->name.') vagy a szuperadmin indítja a Futás fülön.');
});

it('waits for the peers while the diagnosis runs, and asks the admin to start the reading on its day', function () {
    [$setup, $main] = runningDiagnosis();
    $guide = app(StageGuide::class);

    expect($guide->next($main, $setup['admin']))
        ->turn->toBe('peers')
        ->progress->toBe(['done' => 0, 'total' => 2, 'label' => '0 / 2 társ töltötte ki · a csapat-átlaghoz 3 kell'])
        ->cta->toBe(['label' => 'Olvasat indítása', 'href' => route('admin.main-diagnoses.run', $main->diagnosis).'#start-reading']);

    $this->travelTo($main->readingStartsOn());

    expect($guide->next($main, $setup['admin']))
        ->turn->toBe('admin')
        ->body->toStartWith('Elérkezett az olvasat tervezett napja ('.$main->readingStartsOn()->format('Y.m.d.').').');
});

it('warns when the threshold is at risk and when the publishing day has passed', function () {
    [$setup, $main] = runningDiagnosis();
    $guide = app(StageGuide::class);

    $this->travelTo($main->publishes_on->copy()->subDays(3));
    expect(issueTexts($guide->next($main, $setup['admin'])))
        ->toBe(['A küszöb veszélyben: még 3 társ kitöltése kell a csapat-átlaghoz. Dönts a Futás fülön.']);

    $this->travelTo($main->publishes_on->copy()->addDay());
    expect(issueTexts($guide->next($main, $setup['admin'])))
        ->toContain('A tervezett publikálás napja ('.$main->publishes_on->format('Y.m.d.').') elmúlt. Adj új dátumot a Futás fülön.');
});

it('leads to the approvals in the reading, and to Publikálás once the checklist is complete', function () {
    [$setup, $main] = approvedReading();
    $guide = app(StageGuide::class);

    expect($guide->next($main, $setup['admin']))
        ->turn->toBe('admin')
        ->issues->toBe([])
        ->cta->toBe(['label' => 'Publikálás', 'href' => route('admin.main-diagnoses.publish', $main->diagnosis)]);

    DiagnosisClaim::query()->update(['status' => 'draft', 'approved_at' => null]);

    $next = $guide->next($main->refresh(), $setup['admin']);
    expect($next['cta'])->toBe(['label' => 'Jóváhagyás folytatása', 'href' => route('admin.main-diagnoses.reading', $main->diagnosis)])
        ->and(issueTexts($next))->toBe(['Hiányzik: Minden állítás és szöveg jóváhagyva'])
        ->and($next['progress']['done'])->toBeLessThan($next['progress']['total']);
});

it('marks the stages done, current and coming, with their days and who moves them on', function () {
    [, $main] = runningDiagnosis();

    $steps = collect(app(StageGuide::class)->steps($main))->keyBy(fn (array $step) => $step['stage']->value);

    expect($steps->map(fn (array $step) => $step['state'])->all())->toBe([
        'draft' => 'done', 'invited' => 'done', 'filling' => 'done', 'running' => 'current', 'reading' => 'upcoming', 'published' => 'upcoming',
    ])
        ->and($steps['running']['date']->equalTo($main->running_at))->toBeTrue()
        ->and($steps['reading'])->toMatchArray(['planned' => true, 'actor' => 'admin'])
        ->and($steps['reading']['date']->isSameDay($main->readingStartsOn()))->toBeTrue()
        ->and($steps['published']['date']->isSameDay($main->publishes_on))->toBeTrue();
});

it('ends the stage line where the diagnosis was closed', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    app(MainDiagnosisService::class)->close($main, $setup['admin']);

    $steps = app(StageGuide::class)->steps($main->refresh());

    expect(array_map(fn (array $step) => $step['state'], $steps))->toBe(['done', 'done', 'skipped', 'skipped', 'skipped', 'skipped', 'closed'])
        ->and(end($steps)['stage'])->toBe(MainDiagnosisStage::Closed);
});
