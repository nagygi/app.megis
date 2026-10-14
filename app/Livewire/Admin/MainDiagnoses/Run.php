<?php

namespace App\Livewire\Admin\MainDiagnoses;

use App\Enums\MainDiagnosisStage;
use App\Enums\MaterialContext;
use App\Enums\MaterialStatus;
use App\Enums\ParticipantRole;
use App\Enums\ProcessConfidence;
use App\Enums\ProcessKind;
use App\Exceptions\DiagnosisException;
use App\Models\CalendarConnection;
use App\Models\Diagnosis;
use App\Models\MainDiagnosis;
use App\Models\MainDiagnosisParticipant;
use App\Models\Material;
use App\Models\MegisConversation;
use App\Models\Process;
use App\Models\User;
use App\Services\Diagnoses\ThresholdPolicy;
use App\Services\MainDiagnoses\DiagnosisRunService;
use App\Services\MainDiagnoses\StepProgress;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Futás (A2, 19 · 19.3–19.5): where the diagnosis stands, in numbers and
 * states, never answers. The threshold, the reminders, the time window,
 * the start of the reading and the conversations of the mégis.
 */
class Run extends Component
{
    public Diagnosis $diagnosis;

    public string $publishesOn = '';

    public bool $composingThreshold = false;

    public string $thresholdSubject = '';

    public string $thresholdBody = '';

    /**
     * @var array{held_at: string, with_kind: string, with_user_id: ?string, with_name: string, admin_id: ?string, note: string}
     */
    public array $conversation = ['held_at' => '', 'with_kind' => 'leader', 'with_user_id' => null, 'with_name' => '', 'admin_id' => null, 'note' => ''];

    public function mount(Diagnosis $diagnosis): void
    {
        abort_if($diagnosis->main === null, 404);
        Gate::authorize('run', $diagnosis);

        $this->diagnosis = $diagnosis;
        $this->publishesOn = $diagnosis->main->publishes_on->toDateString();
        $this->conversation['admin_id'] = (string) auth('admin')->id();
        $this->conversation['with_user_id'] = (string) $diagnosis->main->leader_id;
    }

    /**
     * One button to the leader who has not finished filling in (19 · 19.4).
     */
    public function remindLeader(DiagnosisRunService $run): void
    {
        $this->authorizeRun();

        $run->remindLeader($this->main(), $this->admin())
            ? session()->flash('status', __('admin.run.reminded_leader'))
            : session()->flash('error', __('admin.run.reminder_limit'));
    }

    /**
     * One button to every peer who has not answered; 48 hours per recipient.
     */
    public function remindPeers(DiagnosisRunService $run): void
    {
        $this->authorizeRun();

        $result = $run->remindPeers($this->main(), $this->admin());
        session()->flash('status', __('admin.run.reminded_peers', $result));
    }

    public function composeThreshold(DiagnosisRunService $run): void
    {
        $this->authorizeRun();

        ['subject' => $this->thresholdSubject, 'body' => $this->thresholdBody] = $run->thresholdDraft($this->main());
        $this->composingThreshold = true;
    }

    /**
     * "Szólok a vezetőnek": the text is edited before sending.
     */
    public function sendThreshold(DiagnosisRunService $run): void
    {
        $this->authorizeRun();
        $this->validate([
            'thresholdSubject' => ['required', 'string', 'max:255'],
            'thresholdBody' => ['required', 'string', 'max:3000'],
        ], attributes: ['thresholdSubject' => __('admin.run.threshold.subject'), 'thresholdBody' => __('admin.run.threshold.body')]);

        if (! $run->notifyThreshold($this->main(), $this->admin(), trim($this->thresholdSubject), trim($this->thresholdBody))) {
            session()->flash('error', __('admin.run.reminder_limit'));

            return;
        }

        $this->composingThreshold = false;
        session()->flash('status', __('admin.run.threshold.sent'));
    }

    public function snoozeThreshold(DiagnosisRunService $run): void
    {
        $this->authorizeRun();

        $run->snoozeThreshold($this->main());
        session()->flash('status', __('admin.run.threshold.snoozed', ['date' => $this->main()->refresh()->threshold_snoozed_until->format('Y.m.d.')]));
    }

    public function overrideThreshold(DiagnosisRunService $run): void
    {
        $this->authorizeRun();

        $this->attempt(fn () => $run->overrideThreshold($this->main(), $this->admin()));
    }

    public function revokeThresholdOverride(DiagnosisRunService $run): void
    {
        $this->authorizeRun();

        $this->attempt(fn () => $run->revokeThresholdOverride($this->main()));
    }

    /**
     * The publishing day changes; the leader gets an e-mail (19 · 19.4).
     */
    public function changePublishDate(DiagnosisRunService $run): void
    {
        $this->authorizeRun();
        $main = $this->main();

        $this->validate([
            'publishesOn' => ['required', 'date', 'after:today', ...($main->starts_on ? ['after_or_equal:'.$main->starts_on->toDateString()] : [])],
        ], attributes: ['publishesOn' => __('admin.main_diagnoses.publishes_on')]);

        $this->attempt(function () use ($run, $main): void {
            $run->changePublishDate($main, $this->publishesOn, $this->admin());
            session()->flash('status', __('admin.run.window_changed'));
        });
    }

    /**
     * "Olvasat indítása" (17 · 17.5); the confirmation is asked in the page.
     */
    public function startReading(DiagnosisRunService $run): void
    {
        $this->authorizeRun();
        abort_unless($this->main()->stage === MainDiagnosisStage::Running, 403);

        $this->attempt(function () use ($run): void {
            $run->startReading($this->main(), $this->admin());
            session()->flash('status', __('admin.run.reading_started'));
            $this->redirect(route('admin.diagnoses.show', $this->diagnosis), navigate: false);
        });
    }

    /**
     * A conversation of the mégis (19 · 19.5): when, with whom, led by whom.
     */
    public function addConversation(DiagnosisRunService $run): void
    {
        $this->authorizeRun();
        $main = $this->main();
        $participantIds = $main->participants()->whereNull('excluded_at')->pluck('user_id')->map(fn ($id) => (string) $id)->all();

        $this->validate([
            'conversation.held_at' => ['required', 'date'],
            'conversation.with_kind' => ['required', Rule::in(MegisConversation::WITH)],
            'conversation.with_user_id' => [Rule::requiredIf($this->conversation['with_kind'] !== 'external'), 'nullable', Rule::in($participantIds)],
            'conversation.with_name' => [Rule::requiredIf($this->conversation['with_kind'] === 'external'), 'nullable', 'string', 'max:255'],
            'conversation.admin_id' => ['nullable', Rule::in(User::query()->admins()->pluck('id')->map(fn ($id) => (string) $id))],
            'conversation.note' => ['nullable', 'string', 'max:2000'],
        ], attributes: [
            'conversation.held_at' => __('admin.run.conversations.held_at'),
            'conversation.with_user_id' => __('admin.run.conversations.with_label'),
            'conversation.with_name' => __('admin.run.conversations.with_name'),
            'conversation.admin_id' => __('admin.run.conversations.admin'),
        ]);

        $run->addConversation($main, [
            'held_at' => $this->conversation['held_at'],
            'with_kind' => $this->conversation['with_kind'],
            'with_user_id' => filled($this->conversation['with_user_id']) ? (int) $this->conversation['with_user_id'] : null,
            'with_name' => $this->conversation['with_name'],
            'admin_id' => filled($this->conversation['admin_id']) ? (int) $this->conversation['admin_id'] : null,
            'note' => $this->conversation['note'],
        ]);

        $this->conversation = [...$this->conversation, 'held_at' => '', 'with_name' => '', 'note' => ''];
        session()->flash('status', __('admin.run.conversations.added'));
    }

    public function removeConversation(int $conversationId): void
    {
        $this->authorizeRun();

        $this->main()->conversations()->whereKey($conversationId)->firstOrFail()->delete();
    }

    public function render(DiagnosisRunService $run, StepProgress $progress, ThresholdPolicy $threshold): View
    {
        $main = $this->main()->load(['leader', 'participants.user', 'conversations.admin', 'conversations.withUser']);
        $organization = $this->diagnosis->organization;
        $peers = $main->participants
            ->filter(fn (MainDiagnosisParticipant $participant) => $participant->role === ParticipantRole::Peer && $participant->excluded_at === null)
            ->values();
        $peerStates = $peers->map(fn (MainDiagnosisParticipant $peer) => [
            'peer' => $peer,
            'state' => match (true) {
                $peer->hasWithdrawn() => 'withdrawn',
                $progress->experienceDiagnosis($main, $peer->user) !== null => 'answered',
                $peer->opened_at !== null => 'opened',
                $peer->invited_at !== null => 'invited',
                default => 'waiting',
            },
        ]);
        $processes = Process::query()->whereBelongsTo($organization)->whereNull('parent_id')->get();
        $materials = Material::query()->where('parent_diagnosis_id', $this->diagnosis->id)->get();

        return view('livewire.admin.main-diagnoses.run', [
            'main' => $main,
            'leaderSteps' => $progress->forLeader($main, $main->leader),
            'leaderSituations' => $materials->where('user_id', $main->leader_id)->where('is_situation', true)->count(),
            'peerStates' => $peerStates,
            'peerCounts' => collect(['invited', 'opened', 'answered', 'withdrawn'])->mapWithKeys(fn (string $state) => [
                $state => match ($state) {
                    'invited' => $peers->whereNotNull('invited_at')->count(),
                    'opened' => $peers->whereNotNull('opened_at')->count(),
                    default => $peerStates->where('state', $state)->count(),
                },
            ]),
            'missingPeers' => $threshold->missingPeers($main),
            'processCounts' => [
                'measured' => $main->processes()->count() ?: $processes->filter(fn (Process $process) => $process->kind === ProcessKind::Routine && $process->isAccepted())->count(),
                'uncertain' => $processes->filter(fn (Process $process) => $process->unclear || in_array($process->confidence, [ProcessConfidence::Medium, ProcessConfidence::Low], true))->count(),
                'excluded' => $processes->where('kind', ProcessKind::Excluded)->count(),
            ],
            'materialCounts' => [
                'uploads' => $materials->where('context', MaterialContext::Occasion)->where('is_situation', false)->count()
                    + $materials->whereIn('context', [MaterialContext::AdminUpload, MaterialContext::Mirror])->count(),
                'situations' => $materials->where('is_situation', true)->count(),
                'processing' => $materials->filter(fn (Material $material) => $material->status->isWorking())->count(),
                'failed' => $materials->where('status', MaterialStatus::Failed)->count(),
                'unclassified' => $materials->filter(fn (Material $material) => $material->isUnclassified())->count(),
            ],
            'connections' => CalendarConnection::query()->whereBelongsTo($organization)->with('user')->latest('id')->get()->unique('user_id'),
            'conversations' => $main->conversations->sortBy('held_at')->values(),
            'thresholdAtRisk' => $run->thresholdAtRisk($main),
            'thresholdMet' => $threshold->isMet($main),
            'thresholdOverrider' => $main->threshold_overridden_by ? User::query()->find($main->threshold_overridden_by) : null,
            'canRemindLeader' => $run->leaderNeedsReminder($main) && $run->canRemind($main, $main->leader),
            'peersToRemind' => $run->peersToRemind($main)->count(),
            'processingCount' => $run->processingCount($main),
            'admins' => User::query()->admins()->orderBy('last_name')->orderBy('first_name')->get(),
            'isOpen' => ! in_array($main->stage, [MainDiagnosisStage::Published, MainDiagnosisStage::Closed], true),
        ])->layout('layouts::admin', ['organization' => $organization])->title($this->diagnosis->name);
    }

    private function main(): MainDiagnosis
    {
        return $this->diagnosis->main;
    }

    private function admin(): User
    {
        return auth('admin')->user();
    }

    private function authorizeRun(): void
    {
        Gate::authorize('run', $this->diagnosis);
    }

    /**
     * Runs a decision and shows a broken rule as an error message.
     */
    private function attempt(callable $change): void
    {
        try {
            $change();
        } catch (DiagnosisException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }
}
