<?php

namespace App\Services\Inbox;

use App\Enums\InboxItemType;
use App\Enums\MainDiagnosisStage;
use App\Enums\MembershipStatus;
use App\Enums\ParticipantRole;
use App\Models\InboxItem;
use App\Models\MainDiagnosis;
use App\Models\MainDiagnosisParticipant;
use App\Models\Membership;
use App\Services\MainDiagnoses\DiagnosisRunService;
use App\Services\MainDiagnoses\StepProgress;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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
     * The keys of a participant's Meghívó rows: five days unopened, expired
     * after fourteen, which comes as a row of its own (22 · 22.2), and a peer
     * who opened T1 but has not started in five days (25 · 25.2).
     *
     * @return list<string>
     */
    public static function invitationKeys(int $participantId): array
    {
        return ['invitation:'.$participantId, 'invitation:'.$participantId.':expired', self::notStartedKey($participantId)];
    }

    public static function notStartedKey(int $participantId): string
    {
        return 'invitation:'.$participantId.':not_started';
    }

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
        $rows = InboxItem::query()
            ->whereIn('type', [InboxItemType::Invitation, InboxItemType::Completion, InboxItemType::Threshold, InboxItemType::Publication])
            ->whereNotNull('dedupe_key')
            // The open rows, and those closed by hand that still hold their key (22 · 22.1).
            ->where(fn ($query) => $query->whereNull('closed_at')->orWhere('close_reason', InboxItem::CLOSED_MANUALLY))
            ->with(['subject' => fn (MorphTo $subject) => $subject->morphWith([MainDiagnosisParticipant::class => ['mainDiagnosis']])])
            ->get();

        foreach ($rows as $item) {
            $resolved = match ($item->type) {
                InboxItemType::Invitation => ! $item->subject instanceof MainDiagnosisParticipant
                    || ! ($item->dedupe_key === self::notStartedKey($item->subject->id) ? $this->notStartedOverdue($item->subject) : $this->invitationOverdue($item->subject)),
                InboxItemType::Completion => ! $item->subject instanceof MainDiagnosis || ! $this->completionOverdue($item->subject),
                InboxItemType::Threshold => ! $item->subject instanceof MainDiagnosis || ! $this->run->thresholdAtRisk($item->subject),
                InboxItemType::Publication => ! $item->subject instanceof MainDiagnosis || ! $this->publicationOverdue($item->subject),
                default => false,
            };

            if (! $resolved) {
                continue;
            }

            if ($item->isOpen()) {
                $closed += $this->inbox->closeAutomatically($item->dedupe_key);
            } else {
                $this->inbox->release($item);
            }
        }

        return $closed;
    }

    /**
     * Five days unopened, or expired after fourteen (22, question 5). An
     * inactive member is not waited for: the organization's pages are closed to them (02).
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
            && $participant->invited_at->lte(now()->subDays(self::INVITATION_DAYS))
            && $this->isActiveMember($participant, $main);
    }

    private function isActiveMember(MainDiagnosisParticipant $participant, MainDiagnosis $main): bool
    {
        return Membership::query()
            ->where('organization_id', $main->diagnosis->organization_id)
            ->where('user_id', $participant->user_id)
            ->where('status', '!=', MembershipStatus::Inactive)
            ->exists();
    }

    /**
     * A peer opened T1 five days ago and pressed "Nem most" or left it (25 · 25.2).
     */
    public function notStartedOverdue(MainDiagnosisParticipant $participant): bool
    {
        $main = $participant->mainDiagnosis;

        return $participant->role === ParticipantRole::Peer
            && $participant->opened_at !== null
            && $participant->started_at === null
            && $participant->excluded_at === null
            && $participant->replaced_at === null
            && $main->stage === MainDiagnosisStage::Running
            && $participant->opened_at->lte(now()->subDays(self::INVITATION_DAYS))
            && $this->isActiveMember($participant, $main);
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
            if ($this->notStartedOverdue($participant->setRelation('mainDiagnosis', $main))) {
                $this->inbox->open(
                    InboxItemType::Invitation,
                    __('admin.inbox.rows.invitation_not_started', [
                        'name' => $participant->user->name,
                        'days' => (int) $participant->opened_at->diffInDays(now()),
                    ]),
                    diagnosis: $main->diagnosis,
                    subject: $participant,
                    dedupeKey: self::notStartedKey($participant->id),
                    staysClosed: true,
                );

                continue;
            }

            if (! $this->invitationOverdue($participant)) {
                continue;
            }

            $expired = $participant->invited_at->lte(now()->subDays(self::INVITATION_VALID_DAYS));
            [$waitingKey, $expiredKey] = self::invitationKeys($participant->id);
            $summary = __($expired ? 'admin.inbox.rows.invitation_expired' : 'admin.inbox.rows.invitation', [
                'name' => $participant->user->name,
                'role' => __('enums.participant_role.'.$participant->role->value),
                'days' => (int) $participant->invited_at->diffInDays(now()),
            ]);

            // The five-day row still open turns into the expired one.
            $waiting = $expired ? InboxItem::query()->open()->where('dedupe_key', $waitingKey)->first() : null;

            if ($waiting !== null) {
                $waiting->forceFill(['summary' => $summary, 'dedupe_key' => $expiredKey])->save();

                continue;
            }

            $this->inbox->open(
                InboxItemType::Invitation,
                $summary,
                diagnosis: $main->diagnosis,
                subject: $participant,
                dedupeKey: $expired ? $expiredKey : $waitingKey,
                staysClosed: true,
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
            staysClosed: true,
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
            staysClosed: true,
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
            staysClosed: true,
        );
    }
}
