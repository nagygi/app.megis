<?php

namespace App\Services\MainDiagnoses;

use App\Enums\MainDiagnosisStage;
use App\Enums\MaterialContext;
use App\Enums\MaterialStatus;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\User;
use App\Services\Diagnoses\ReadingService;
use App\Services\Diagnoses\ThresholdPolicy;
use App\Services\Inbox\InboxScanner;
use App\Services\Report\PublishService;
use Carbon\CarbonInterface;

/**
 * Where a main diagnosis stands and what comes next (17 · 17.5, 19): the
 * stage line of its band and the "Most mi jön?" card under it. Who moves each
 * stage on, the dates done and planned, and what holds the next step up, so
 * the admin sees on every tab what to click and where. It only reads: the
 * actions stay on their tabs, the card links there.
 */
class StageGuide
{
    /**
     * The stages of the line in order; a closed diagnosis ends where it was closed.
     *
     * @var list<MainDiagnosisStage>
     */
    public const STAGES = [
        MainDiagnosisStage::Draft,
        MainDiagnosisStage::Invited,
        MainDiagnosisStage::Filling,
        MainDiagnosisStage::Running,
        MainDiagnosisStage::Reading,
        MainDiagnosisStage::Published,
    ];

    /**
     * Who moves the diagnosis into a stage: the admin saves and invites,
     * starts the reading and publishes; the leader logs in and starts it.
     */
    private const ACTORS = [
        'draft' => 'admin',
        'invited' => 'admin',
        'filling' => 'leader',
        'running' => 'leader',
        'reading' => 'admin',
        'published' => 'admin',
        'closed' => 'admin',
    ];

    public function __construct(
        private MainDiagnosisService $mainDiagnoses,
        private StepProgress $progress,
        private DiagnosisRunService $run,
        private ThresholdPolicy $threshold,
        private ReadingService $reading,
        private PublishService $publish,
    ) {}

    /**
     * The stage line: done stages with their day, the current one since when,
     * the coming ones with the planned day where there is one (the leader's
     * deadline, the reading, publishing).
     *
     * @return list<array{stage: MainDiagnosisStage, state: 'done'|'current'|'upcoming'|'skipped'|'closed', date: ?CarbonInterface, planned: bool, actor: string}>
     */
    public function steps(MainDiagnosis $main): array
    {
        $closed = $main->stage === MainDiagnosisStage::Closed;
        $reachedIndex = (int) array_search($closed ? $this->stageBeforeClosing($main) : $main->stage, self::STAGES, true);
        $steps = [];

        foreach (self::STAGES as $index => $stage) {
            $state = match (true) {
                $index < $reachedIndex => 'done',
                $index === $reachedIndex => $closed ? 'done' : 'current',
                default => $closed ? 'skipped' : 'upcoming',
            };
            [$date, $planned] = $this->date($main, $stage, $state);

            $steps[] = ['stage' => $stage, 'state' => $state, 'date' => $date, 'planned' => $planned, 'actor' => self::ACTORS[$stage->value]];
        }

        if ($closed) {
            $steps[] = ['stage' => MainDiagnosisStage::Closed, 'state' => 'closed', 'date' => $main->closed_at, 'planned' => false, 'actor' => self::ACTORS['closed']];
        }

        return $steps;
    }

    /**
     * The "Most mi jön?" card: whose turn it is, what happens, what holds it
     * up, and the link to the tab where the next action is.
     *
     * @return array{turn: 'admin'|'leader'|'peers'|'done'|'closed', title: string, body: string, progress: ?array{done: int, total: int, label: string}, issues: list<array{tone: 'warning'|'error', text: string, href: ?string}>, cta: ?array{label: string, href: string}, note: ?string}
     */
    public function next(MainDiagnosis $main, ?User $admin): array
    {
        $main->loadMissing(['leader', 'diagnosis.organization.contactAdmin']);
        $canRun = (bool) $admin?->can('run', $main->diagnosis);

        return match ($main->stage) {
            MainDiagnosisStage::Draft => $this->draft($main),
            MainDiagnosisStage::Invited, MainDiagnosisStage::Filling => $this->leaderTurn($main, $canRun),
            MainDiagnosisStage::Running => $this->running($main, $canRun),
            MainDiagnosisStage::Reading => $this->readingTurn($main, $canRun),
            MainDiagnosisStage::Published => $this->card(
                turn: 'done',
                title: __('admin.main_diagnoses.guide.published.title'),
                body: __('admin.main_diagnoses.guide.published.body', ['date' => $main->published_at?->format('Y.m.d.') ?? '–']),
                cta: ['label' => __('admin.main_diagnoses.guide.published.cta'), 'href' => route('admin.main-diagnoses.publish', $main->diagnosis)],
            ),
            MainDiagnosisStage::Closed => $this->card(
                turn: 'closed',
                title: __('admin.main_diagnoses.guide.closed.title'),
                body: __('admin.main_diagnoses.guide.closed.body', ['date' => $main->closed_at?->format('Y.m.d.') ?? '–']),
            ),
        };
    }

    /**
     * @return array{turn: string, title: string, body: string, progress: ?array{done: int, total: int, label: string}, issues: list<array{tone: string, text: string, href: ?string}>, cta: ?array{label: string, href: string}, note: ?string}
     */
    private function draft(MainDiagnosis $main): array
    {
        $organization = $main->diagnosis->organization;
        $links = [
            __('admin.main_diagnoses.blocker_contact') => route('admin.main-diagnoses.edit', $main->diagnosis).'#contact_admin_id',
            __('admin.main_diagnoses.blocker_values') => route('admin.organizations.values', $organization),
            __('admin.main_diagnoses.blocker_leader_inactive') => route('admin.organizations.members', $organization),
        ];
        $issues = array_map(
            fn (string $blocker) => ['tone' => 'error', 'text' => $blocker, 'href' => $links[$blocker] ?? null],
            $this->mainDiagnoses->invitationBlockers($main),
        );

        return $this->card(
            turn: 'admin',
            title: __('admin.main_diagnoses.guide.draft.title'),
            body: __('admin.main_diagnoses.guide.draft.body', ['name' => $main->leader->name]),
            issues: $issues,
            cta: $issues === [] ? ['label' => __('admin.main_diagnoses.send_invitation'), 'href' => route('admin.main-diagnoses.edit', $main->diagnosis).'#invitation'] : null,
        );
    }

    /**
     * Meghívva and Kitöltés: the leader logs in, fills the steps and starts it.
     *
     * @return array{turn: string, title: string, body: string, progress: ?array{done: int, total: int, label: string}, issues: list<array{tone: string, text: string, href: ?string}>, cta: ?array{label: string, href: string}, note: ?string}
     */
    private function leaderTurn(MainDiagnosis $main, bool $canRun): array
    {
        $invited = $main->stage === MainDiagnosisStage::Invited;
        $steps = $this->progress->forLeader($main, $main->leader);
        $issues = [];

        if ($invited && $main->invited_at?->lte(now()->subDays(InboxScanner::INVITATION_DAYS))) {
            $days = (int) $main->invited_at->diffInDays(now());
            $issues[] = [
                'tone' => $days >= InboxScanner::INVITATION_VALID_DAYS ? 'error' : 'warning',
                'text' => __($days >= InboxScanner::INVITATION_VALID_DAYS ? 'admin.main_diagnoses.guide.issues.invitation_expired' : 'admin.main_diagnoses.guide.issues.invitation_unopened', ['days' => $days]),
                'href' => route('admin.inbox.index'),
            ];
        }

        if ($main->starts_on !== null && $main->starts_on->lte(today())) {
            $issues[] = $main->starts_on->isToday()
                ? ['tone' => 'warning', 'text' => __('admin.main_diagnoses.guide.issues.deadline_today'), 'href' => null]
                : ['tone' => 'warning', 'text' => __('admin.main_diagnoses.guide.issues.deadline_passed', ['date' => $main->starts_on->format('Y.m.d.'), 'days' => (int) $main->starts_on->diffInDays(today())]), 'href' => null];
        }

        return $this->card(
            turn: 'leader',
            title: __($invited ? 'admin.main_diagnoses.guide.invited.title' : 'admin.main_diagnoses.guide.filling.title'),
            body: __($invited ? 'admin.main_diagnoses.guide.invited.body' : 'admin.main_diagnoses.guide.filling.body', [
                'name' => $main->leader->name,
                'date' => $main->invited_at?->format('Y.m.d.') ?? '',
            ]),
            progress: $invited ? null : ['done' => $steps->doneCount(), 'total' => $steps->count(), 'label' => __('admin.main_diagnoses.guide.filling.progress', ['done' => $steps->doneCount(), 'total' => $steps->count()])],
            issues: $issues,
            cta: $canRun ? ['label' => __($this->run->leaderNeedsReminder($main) && $this->run->canRemind($main, $main->leader) ? 'admin.main_diagnoses.guide.remind_leader' : 'admin.main_diagnoses.guide.leader_steps'), 'href' => route('admin.main-diagnoses.run', $main->diagnosis).'#leader'] : null,
            note: $canRun ? null : __('admin.main_diagnoses.guide.by_contact_remind', ['name' => $this->contactName($main)]),
        );
    }

    /**
     * Fut: the peers fill in, materials come in; the admin starts the reading.
     *
     * @return array{turn: string, title: string, body: string, progress: ?array{done: int, total: int, label: string}, issues: list<array{tone: string, text: string, href: ?string}>, cta: ?array{label: string, href: string}, note: ?string}
     */
    private function running(MainDiagnosis $main, bool $canRun): array
    {
        $readingOn = $main->readingStartsOn();
        $due = $readingOn->lte(today());
        $runUrl = route('admin.main-diagnoses.run', $main->diagnosis);
        $issues = [];
        $progress = null;

        if ($main->hasPeers()) {
            $peers = $main->peers()->with('user')->get()->reject->hasWithdrawn();
            $answered = $peers->filter(fn ($peer) => $this->progress->experienceDiagnosis($main, $peer->user) !== null)->count();
            $progress = ['done' => $answered, 'total' => $peers->count(), 'label' => __('admin.main_diagnoses.guide.running.progress', ['done' => $answered, 'total' => $peers->count(), 'threshold' => ThresholdPolicy::PEERS])];
        }

        if ($this->run->thresholdAtRisk($main)) {
            $issues[] = ['tone' => 'warning', 'text' => __('admin.main_diagnoses.guide.issues.threshold', ['count' => $this->threshold->missingPeers($main)]), 'href' => $canRun ? $runUrl.'#threshold-title' : null];
        }

        $materials = Material::query()->where('parent_diagnosis_id', $main->diagnosis_id);
        $processing = $this->run->processingCount($main);
        $failed = (clone $materials)->where('status', MaterialStatus::Failed)->count();
        $unclassified = (clone $materials)->where('context', MaterialContext::Occasion)->whereNull('process_occasion_id')->whereNull('classified_other_at')->count();

        if ($failed > 0) {
            $issues[] = ['tone' => 'error', 'text' => __('admin.main_diagnoses.guide.issues.materials_failed', ['count' => $failed]), 'href' => route('admin.inbox.index')];
        }

        if ($processing > 0) {
            $issues[] = ['tone' => 'warning', 'text' => __('admin.main_diagnoses.guide.issues.materials_processing', ['count' => $processing]), 'href' => null];
        }

        if ($unclassified > 0) {
            $issues[] = ['tone' => 'warning', 'text' => __('admin.main_diagnoses.guide.issues.materials_unclassified', ['count' => $unclassified]), 'href' => route('admin.inbox.index')];
        }

        if ($main->publishes_on->lt(today())) {
            $issues[] = ['tone' => 'error', 'text' => __('admin.main_diagnoses.guide.issues.publish_passed', ['date' => $main->publishes_on->format('Y.m.d.')]), 'href' => $canRun ? $runUrl.'#window-title' : null];
        }

        return $this->card(
            turn: $due ? 'admin' : ($main->hasPeers() ? 'peers' : 'admin'),
            title: __($main->hasPeers() ? 'admin.main_diagnoses.guide.running.title' : 'admin.main_diagnoses.guide.running.title_alone'),
            body: __($due ? 'admin.main_diagnoses.guide.running.body_due' : 'admin.main_diagnoses.guide.running.body', [
                'date' => $readingOn->format('Y.m.d.'),
                'days' => (int) today()->diffInDays($readingOn),
            ]),
            progress: $progress,
            issues: $issues,
            cta: $canRun ? ['label' => __('admin.run.start_reading'), 'href' => $runUrl.'#start-reading'] : null,
            note: $canRun ? null : __('admin.main_diagnoses.guide.by_contact_reading', ['name' => $this->contactName($main)]),
        );
    }

    /**
     * Olvasat: the admin approves the drafts, then publishes.
     *
     * @return array{turn: string, title: string, body: string, progress: ?array{done: int, total: int, label: string}, issues: list<array{tone: string, text: string, href: ?string}>, cta: ?array{label: string, href: string}, note: ?string}
     */
    private function readingTurn(MainDiagnosis $main, bool $canRun): array
    {
        $diagnosis = $main->diagnosis;
        $progress = $this->reading->progress($main);
        $done = $progress['rows'] + $progress['claims'] + $progress['texts'];
        $total = $progress['rows_total'] + $progress['claims_total'] + $progress['texts_total'];
        $checklist = $this->publish->checklist($main);
        $ready = ! in_array(false, $checklist, true);
        $links = [
            'rows' => route('admin.main-diagnoses.reading', $diagnosis),
            'claims' => route('admin.main-diagnoses.reading', $diagnosis).'#claims-title',
            'classified' => route('admin.organizations.processes', $diagnosis->organization),
            'threshold' => $canRun ? route('admin.main-diagnoses.run', $diagnosis).'#threshold-title' : null,
            'capabilities' => route('admin.diagnoses.capabilities', $diagnosis),
        ];
        $issues = [];

        foreach ($checklist as $key => $ok) {
            if (! $ok) {
                $issues[] = ['tone' => 'warning', 'text' => __('admin.main_diagnoses.guide.issues.missing', ['item' => __('admin.publish.items.'.$key)]), 'href' => $links[$key] ?? null];
            }
        }

        if ($main->publishes_on->lt(today())) {
            $issues[] = ['tone' => 'error', 'text' => __('admin.main_diagnoses.guide.issues.publish_passed', ['date' => $main->publishes_on->format('Y.m.d.')]), 'href' => $canRun ? route('admin.main-diagnoses.run', $diagnosis).'#window-title' : null];
        }

        return $this->card(
            turn: 'admin',
            title: __($ready ? 'admin.main_diagnoses.guide.reading.title_ready' : 'admin.main_diagnoses.guide.reading.title'),
            body: __($ready ? 'admin.main_diagnoses.guide.reading.body_ready' : 'admin.main_diagnoses.guide.reading.body', ['date' => $main->publishes_on->format('Y.m.d.')]),
            progress: ['done' => $done, 'total' => $total, 'label' => __('admin.main_diagnoses.guide.reading.progress', ['done' => $done, 'total' => $total])],
            issues: $issues,
            cta: $ready
                ? ['label' => __('admin.main_diagnoses.guide.reading.cta_publish'), 'href' => route('admin.main-diagnoses.publish', $diagnosis)]
                : ['label' => __('admin.main_diagnoses.guide.reading.cta_reading'), 'href' => route('admin.main-diagnoses.reading', $diagnosis)],
        );
    }

    /**
     * @param  list<array{tone: string, text: string, href: ?string}>  $issues
     * @param  array{done: int, total: int, label: string}|null  $progress
     * @param  array{label: string, href: string}|null  $cta
     * @return array{turn: string, title: string, body: string, progress: ?array{done: int, total: int, label: string}, issues: list<array{tone: string, text: string, href: ?string}>, cta: ?array{label: string, href: string}, note: ?string}
     */
    private function card(string $turn, string $title, string $body, ?array $progress = null, array $issues = [], ?array $cta = null, ?string $note = null): array
    {
        return compact('turn', 'title', 'body', 'progress', 'issues', 'cta', 'note');
    }

    /**
     * The day a stage was reached (done or current), or its planned day (coming).
     *
     * @return array{0: ?CarbonInterface, 1: bool}
     */
    private function date(MainDiagnosis $main, MainDiagnosisStage $stage, string $state): array
    {
        if ($state === 'skipped') {
            return [null, false];
        }

        if ($state !== 'upcoming') {
            return [match ($stage) {
                MainDiagnosisStage::Draft => $main->created_at,
                MainDiagnosisStage::Invited => $main->invited_at,
                MainDiagnosisStage::Filling => $this->transitionDate($main, MainDiagnosisStage::Filling),
                MainDiagnosisStage::Running => $main->running_at,
                MainDiagnosisStage::Reading => $main->reading_at,
                MainDiagnosisStage::Published => $main->published_at,
                MainDiagnosisStage::Closed => $main->closed_at,
            }, false];
        }

        $planned = match ($stage) {
            MainDiagnosisStage::Running => $main->starts_on,
            MainDiagnosisStage::Reading => $main->readingStartsOn(),
            MainDiagnosisStage::Published => $main->publishes_on,
            default => null,
        };

        return [$planned, $planned !== null];
    }

    private function transitionDate(MainDiagnosis $main, MainDiagnosisStage $stage): ?CarbonInterface
    {
        return $main->diagnosis->transitions()->where('to_stage', $stage)->latest('id')->first()?->created_at;
    }

    /**
     * The stage the diagnosis was in when it was closed, from its log.
     */
    private function stageBeforeClosing(MainDiagnosis $main): MainDiagnosisStage
    {
        $from = $main->diagnosis->transitions()->where('to_stage', MainDiagnosisStage::Closed)->latest('id')->first()?->from_stage;

        return in_array($from, self::STAGES, true) ? $from : MainDiagnosisStage::Draft;
    }

    private function contactName(MainDiagnosis $main): string
    {
        return $main->diagnosis->organization->contactAdmin?->name ?? '–';
    }
}
