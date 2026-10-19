<?php

namespace App\Services\MainDiagnoses;

use App\Enums\MainDiagnosisStage;
use App\Enums\MaterialStatus;
use App\Enums\ParticipantRole;
use App\Exceptions\DiagnosisException;
use App\Mail\DiagnosisNoticeMail;
use App\Models\MainDiagnosis;
use App\Models\MainDiagnosisParticipant;
use App\Models\Material;
use App\Models\MegisConversation;
use App\Models\ReminderDispatch;
use App\Models\User;
use App\Services\Diagnoses\ThresholdPolicy;
use App\Services\Inbox\InboxService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * The decisions and actions of the Futás tab (19 · 19.4): reminders with a
 * 48-hour limit per recipient, the threshold, the time window, the start of
 * the reading and the conversations of the mégis (19.5).
 */
class DiagnosisRunService
{
    public const REMINDER_HOURS = 48;

    public const SNOOZE_DAYS = 5;

    /**
     * Below this many days before publishing a missing threshold is flagged.
     */
    public const THRESHOLD_WARNING_DAYS = 7;

    public function __construct(
        private StepProgress $progress,
        private StageMachine $stages,
        private ThresholdPolicy $threshold,
    ) {}

    public function canRemind(MainDiagnosis $main, User $user): bool
    {
        return ! ReminderDispatch::query()
            ->where('main_diagnosis_id', $main->id)
            ->where('user_id', $user->id)
            ->where('created_at', '>', now()->subHours(self::REMINDER_HOURS))
            ->exists();
    }

    /**
     * The leader has steps left while the diagnosis has not started.
     */
    public function leaderNeedsReminder(MainDiagnosis $main): bool
    {
        return $main->stage->isBeforeRunning()
            && $main->stage !== MainDiagnosisStage::Draft
            && $this->progress->forLeader($main, $main->leader)->firstMissing() !== null;
    }

    /**
     * Peers who have not sent their T4 yet, without those who withdrew.
     *
     * @return Collection<int, MainDiagnosisParticipant>
     */
    public function peersToRemind(MainDiagnosis $main): Collection
    {
        if (! $main->hasPeers() || $main->stage !== MainDiagnosisStage::Running) {
            return collect();
        }

        return $main->peers()->with('user')->get()
            ->reject(fn (MainDiagnosisParticipant $peer) => $peer->hasWithdrawn() || $this->progress->experienceDiagnosis($main, $peer->user) !== null)
            ->values();
    }

    /**
     * "Még {n} lépés van hátra…" to the leader (19 · E-mailek).
     */
    public function remindLeader(MainDiagnosis $main, User $admin): bool
    {
        $leader = $main->leader;

        if (! $this->leaderNeedsReminder($main) || ! $this->canRemind($main, $leader)) {
            return false;
        }

        $steps = $this->progress->forLeader($main, $leader);
        $diagnosis = $main->diagnosis;

        $this->send($main, $leader, ReminderDispatch::LEADER, $admin, new DiagnosisNoticeMail(
            __('mail.diagnosis_reminder.leader_subject', ['subject' => $main->subjectName()]),
            __('mail.diagnosis_reminder.leader_body', ['count' => $steps->count() - $steps->doneCount(), 'subject' => $main->subjectName(), 'minutes' => $steps->remainingMinutes()]),
            __('mail.diagnosis_reminder.leader_action'),
            route('client.diagnosis.show', [$diagnosis->organization, $diagnosis]),
        ));

        return true;
    }

    /**
     * "{Vezető} kíváncsi, hogyan látod te." to every peer who has not answered.
     *
     * @return array{sent: int, skipped: int}
     */
    public function remindPeers(MainDiagnosis $main, User $admin): array
    {
        $sent = 0;
        $skipped = 0;
        $diagnosis = $main->diagnosis;

        foreach ($this->peersToRemind($main) as $peer) {
            if (! $this->canRemind($main, $peer->user)) {
                $skipped++;

                continue;
            }

            $this->send($main, $peer->user, ReminderDispatch::PEER, $admin, new DiagnosisNoticeMail(
                __('mail.diagnosis_reminder.peer_subject', ['leader' => $main->leader->name]),
                __('mail.diagnosis_reminder.peer_body', ['leader' => $main->leader->name, 'minutes' => (int) config('diagnosis.peer_minutes')]),
                __('mail.diagnosis_reminder.peer_action'),
                route('client.diagnosis.show', [$diagnosis->organization, $diagnosis]),
            ));
            $sent++;
        }

        return ['sent' => $sent, 'skipped' => $skipped];
    }

    /**
     * The Beérkező's "Emlékeztetem" for one participant (22 · 22.2).
     */
    public function remindParticipant(MainDiagnosis $main, MainDiagnosisParticipant $participant, User $admin): bool
    {
        if ($participant->role === ParticipantRole::Leader) {
            return $this->remindLeader($main, $admin);
        }

        if (! $this->canRemind($main, $participant->user)) {
            return false;
        }

        $diagnosis = $main->diagnosis;

        $this->send($main, $participant->user, ReminderDispatch::PEER, $admin, new DiagnosisNoticeMail(
            __('mail.diagnosis_reminder.peer_subject', ['leader' => $main->leader->name]),
            __('mail.diagnosis_reminder.peer_body', ['leader' => $main->leader->name, 'minutes' => (int) config('diagnosis.peer_minutes')]),
            __('mail.diagnosis_reminder.peer_action'),
            route('client.diagnosis.show', [$diagnosis->organization, $diagnosis]),
        ));

        return true;
    }

    /**
     * "A küszöb veszélyben" (19 · 19.4): fewer than 7 days to publishing,
     * fewer than three peers, no decision and no snooze running.
     */
    public function thresholdAtRisk(MainDiagnosis $main): bool
    {
        return $main->stage === MainDiagnosisStage::Running
            && $main->hasPeers()
            && ! $this->threshold->isMet($main)
            && ! $this->threshold->isOverridden($main)
            && ($main->threshold_snoozed_until === null || $main->threshold_snoozed_until->isPast())
            && today()->diffInDays($main->publishes_on, false) < self::THRESHOLD_WARNING_DAYS;
    }

    /**
     * Below the threshold the admin can let it go without the team number at
     * any time before publishing, also after the reading has started early:
     * the publishing checklist asks for this decision (19 · 19.4, 21 · 21.2).
     */
    public function thresholdOverridable(MainDiagnosis $main): bool
    {
        return in_array($main->stage, [MainDiagnosisStage::Running, MainDiagnosisStage::Reading], true)
            && $main->hasPeers()
            && ! $this->threshold->isMet($main)
            && ! $this->threshold->isOverridden($main);
    }

    /**
     * The threshold text for the leader, editable before sending.
     *
     * @return array{subject: string, body: string}
     */
    public function thresholdDraft(MainDiagnosis $main): array
    {
        return [
            'subject' => __('mail.diagnosis_reminder.threshold_subject', ['subject' => $main->subjectName()]),
            'body' => __('mail.diagnosis_reminder.threshold_body', [
                'answered' => $this->threshold->peersAnswered($main),
                'missing' => $this->threshold->missingPeers($main),
            ]),
        ];
    }

    /**
     * "Szólok a vezetőnek": the edited text goes out.
     */
    public function notifyThreshold(MainDiagnosis $main, User $admin, string $subject, string $body): bool
    {
        if (! $this->canRemind($main, $main->leader)) {
            return false;
        }

        $diagnosis = $main->diagnosis;

        $this->send($main, $main->leader, ReminderDispatch::THRESHOLD, $admin, new DiagnosisNoticeMail(
            $subject,
            $body,
            __('mail.diagnosis_reminder.threshold_action'),
            route('client.diagnosis.show', [$diagnosis->organization, $diagnosis]),
        ));

        return true;
    }

    /**
     * "Várok 5 napot": the flag is quiet for five days; publishing does not move.
     */
    public function snoozeThreshold(MainDiagnosis $main): void
    {
        $main->update(['threshold_snoozed_until' => today()->addDays(self::SNOOZE_DAYS)]);
    }

    /**
     * "Csapat-szám nélkül megy" (18 · 18.6): who and when is kept.
     */
    public function overrideThreshold(MainDiagnosis $main, User $admin): void
    {
        $this->ensureBeforePublishing($main);

        $main->update(['threshold_overridden_at' => now(), 'threshold_overridden_by' => $admin->id]);
        app(InboxService::class)->closeAutomatically('threshold:'.$main->id);
    }

    /**
     * The decision can be taken back until publishing (19 · 19.4).
     */
    public function revokeThresholdOverride(MainDiagnosis $main): void
    {
        $this->ensureBeforePublishing($main);

        $main->update(['threshold_overridden_at' => null, 'threshold_overridden_by' => null]);
    }

    /**
     * The publishing day changes; the leader is told (19 · 19.4).
     */
    public function changePublishDate(MainDiagnosis $main, string $date, User $admin): void
    {
        $this->ensureBeforePublishing($main);

        $main->update(['publishes_on' => $date]);
        $main->diagnosis->update(['diagnosed_until' => $date]);

        $this->notifyWindowChanged($main->refresh(), $admin);
    }

    /**
     * "A {Csapat} diagnózisa {dátum}-ra készül el."
     */
    public function notifyWindowChanged(MainDiagnosis $main, ?User $admin = null): void
    {
        if ($main->stage === MainDiagnosisStage::Draft) {
            return;
        }

        $diagnosis = $main->diagnosis;

        $this->send($main, $main->leader, ReminderDispatch::WINDOW, $admin, new DiagnosisNoticeMail(
            __('mail.diagnosis_reminder.window_subject', ['subject' => $main->subjectName()]),
            __('mail.diagnosis_reminder.window_body', ['subject' => $main->subjectName(), 'date' => $main->publishes_on->format('Y.m.d.')]),
            __('mail.diagnosis_reminder.window_action'),
            route('client.diagnosis.show', [$diagnosis->organization, $diagnosis]),
        ));
    }

    /**
     * Materials still being processed when reading starts (19 · 19.4).
     */
    public function processingCount(MainDiagnosis $main): int
    {
        return Material::query()
            ->where('parent_diagnosis_id', $main->diagnosis_id)
            ->whereIn('status', [MaterialStatus::Pending, MaterialStatus::Transcribing, MaterialStatus::Analyzing])
            ->count();
    }

    public function startReading(MainDiagnosis $main, User $admin): void
    {
        $this->stages->transition($main, MainDiagnosisStage::Reading, $admin);
    }

    /**
     * @param  array{held_at: string, with_kind: string, with_user_id: ?int, with_name: ?string, admin_id: ?int, note: ?string}  $data
     */
    public function addConversation(MainDiagnosis $main, array $data): MegisConversation
    {
        return $main->conversations()->create([
            'held_at' => CarbonImmutable::parse($data['held_at']),
            'with_kind' => $data['with_kind'],
            'with_user_id' => $data['with_kind'] === 'external' ? null : $data['with_user_id'],
            'with_name' => $data['with_kind'] === 'external' ? trim((string) $data['with_name']) : null,
            'admin_id' => $data['admin_id'],
            'note' => filled($data['note']) ? trim($data['note']) : null,
        ]);
    }

    private function send(MainDiagnosis $main, User $recipient, string $kind, ?User $admin, DiagnosisNoticeMail $mail): void
    {
        DB::transaction(function () use ($main, $recipient, $kind, $admin, $mail): void {
            // The window notice is information, not a reminder: it does not use up the 48 hours.
            if ($kind !== ReminderDispatch::WINDOW) {
                ReminderDispatch::create(['main_diagnosis_id' => $main->id, 'user_id' => $recipient->id, 'kind' => $kind, 'sent_by' => $admin?->id]);
            }

            Mail::to($recipient)->send($mail);
        });
    }

    private function ensureBeforePublishing(MainDiagnosis $main): void
    {
        if (in_array($main->stage, [MainDiagnosisStage::Published, MainDiagnosisStage::Closed], true)) {
            throw new DiagnosisException(__('admin.run.after_publishing'));
        }
    }

    /**
     * Whether a user takes part in the diagnosis as the leader or a peer.
     */
    public function isParticipant(MainDiagnosis $main, int $userId): bool
    {
        return $main->participants()
            ->where('user_id', $userId)
            ->whereIn('role', [ParticipantRole::Leader, ParticipantRole::Peer])
            ->whereNull('excluded_at')
            ->exists();
    }
}
