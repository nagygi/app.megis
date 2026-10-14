<?php

namespace App\Services\Inbox;

use App\Enums\InboxItemType;
use App\Enums\MainDiagnosisStage;
use App\Enums\ParticipantRole;
use App\Models\InboxItem;
use App\Models\MainDiagnosis;
use App\Models\MainDiagnosisParticipant;
use App\Services\MainDiagnoses\DiagnosisRunService;
use App\Services\MainDiagnoses\StepProgress;

/**
 * The daily scan of the Beérkező (22 · 22.2): invitations not opened in five
 * days or expired, a leader who has not started by the starting day, a
 * threshold in danger, a publishing day passed. A row whose cause went
 * away closes by itself (22 · 22.3).
 */
class InboxScanner
{
    public const INVITATION_DAYS = 5;

    public const INVITATION_VALID_DAYS = 14;

    public function __construct(
        private InboxService $inbox,
        private DiagnosisRunService $run,
        private StepProgress $progress,
    ) {}

    /**
     * @return array{opened: int, closed: int}
     */
    public function scan(): array
    {
        $before = InboxItem::query()->open()->count();
        $closed = $this->closeResolved();

        $mains = MainDiagnosis::query()
            ->whereNotIn('stage', [MainDiagnosisStage::Draft->value, MainDiagnosisStage::Published->value, MainDiagnosisStage::Closed->value])
            ->with(['diagnosis.organization', 'leader', 'participants.user'])
            ->get();

        foreach ($mains as $main) {
            $this->invitations($main);
            $this->completion($main);
            $this->threshold($main);
            $this->publication($main);
        }

        return ['opened' => InboxItem::query()->open()->count() - ($before - $closed), 'closed' => $closed];
    }

    /**
     * Rows of causes that went away: the invitation was opened, the diagnosis
     * started, the threshold was met or decided, the diagnosis was published.
     */
    public function closeResolved(): int
    {
        $closed = 0;

        foreach (InboxItem::query()->open()->whereIn('type', [InboxItemType::Invitation, InboxItemType::Completion, InboxItemType::Threshold, InboxItemType::Publication])->with('subject')->get() as $item) {
            $resolved = match ($item->type) {
                InboxItemType::Invitation => ! $item->subject instanceof MainDiagnosisParticipant || ! $this->invitationOverdue($item->subject),
                InboxItemType::Completion => ! $item->subject instanceof MainDiagnosis || ! $this->completionOverdue($item->subject),
                InboxItemType::Threshold => ! $item->subject instanceof MainDiagnosis || ! $this->run->thresholdAtRisk($item->subject),
                InboxItemType::Publication => ! $item->subject instanceof MainDiagnosis || ! $this->publicationOverdue($item->subject),
                default => false,
            };

            if ($resolved && $item->dedupe_key !== null) {
                $closed += $this->inbox->closeAutomatically($item->dedupe_key);
            }
        }

        return $closed;
    }

    /**
     * Five days unopened, or expired after fourteen (22, question 5).
     */
    public function invitationOverdue(MainDiagnosisParticipant $participant): bool
    {
        $main = $participant->mainDiagnosis;

        return $participant->invited_at !== null
            && $participant->opened_at === null
            && $participant->excluded_at === null
            && $participant->replaced_at === null
            && ! in_array($main->stage, [MainDiagnosisStage::Published, MainDiagnosisStage::Closed, MainDiagnosisStage::Reading], true)
            // A running diagnosis was started by its leader.
            && ($participant->role !== ParticipantRole::Leader || $main->stage->isBeforeRunning())
            && $participant->invited_at->lte(now()->subDays(self::INVITATION_DAYS));
    }

    /**
     * The leader has not pressed "Indul a diagnózis" by the starting day (22, question 6).
     */
    public function completionOverdue(MainDiagnosis $main): bool
    {
        return in_array($main->stage, [MainDiagnosisStage::Invited, MainDiagnosisStage::Filling], true)
            && $main->starts_on !== null
            && $main->starts_on->lte(today());
    }

    public function publicationOverdue(MainDiagnosis $main): bool
    {
        return in_array($main->stage, [MainDiagnosisStage::Running, MainDiagnosisStage::Reading], true)
            && $main->publishes_on->lt(today());
    }

    private function invitations(MainDiagnosis $main): void
    {
        foreach ($main->participants as $participant) {
            if (! $this->invitationOverdue($participant->setRelation('mainDiagnosis', $main))) {
                continue;
            }

            $expired = $participant->invited_at->lte(now()->subDays(self::INVITATION_VALID_DAYS));

            $this->inbox->open(
                InboxItemType::Invitation,
                __($expired ? 'admin.inbox.rows.invitation_expired' : 'admin.inbox.rows.invitation', [
                    'name' => $participant->user->name,
                    'role' => __('enums.participant_role.'.$participant->role->value),
                    'days' => (int) $participant->invited_at->diffInDays(now()),
                ]),
                diagnosis: $main->diagnosis,
                subject: $participant,
                dedupeKey: 'invitation:'.$participant->id,
            );
        }
    }

    private function completion(MainDiagnosis $main): void
    {
        if (! $this->completionOverdue($main)) {
            return;
        }

        $steps = $this->progress->forLeader($main, $main->leader);

        $this->inbox->open(
            InboxItemType::Completion,
            __('admin.inbox.rows.completion', ['name' => $main->leader->name, 'done' => $steps->doneCount(), 'total' => $steps->count()]),
            diagnosis: $main->diagnosis,
            subject: $main,
            dedupeKey: 'completion:'.$main->id,
        );
    }

    private function threshold(MainDiagnosis $main): void
    {
        if (! $this->run->thresholdAtRisk($main)) {
            return;
        }

        $this->inbox->open(
            InboxItemType::Threshold,
            __('admin.inbox.rows.threshold', ['answered' => (int) ($main->calculation['peers_answered'] ?? 0), 'date' => $main->publishes_on->format('m.d.')]),
            diagnosis: $main->diagnosis,
            subject: $main,
            dedupeKey: 'threshold:'.$main->id,
        );
    }

    private function publication(MainDiagnosis $main): void
    {
        if (! $this->publicationOverdue($main)) {
            return;
        }

        $this->inbox->open(
            InboxItemType::Publication,
            __('admin.inbox.rows.publication', ['date' => $main->publishes_on->format('Y.m.d.')]),
            diagnosis: $main->diagnosis,
            subject: $main,
            dedupeKey: 'publication:'.$main->id,
        );
    }
}
