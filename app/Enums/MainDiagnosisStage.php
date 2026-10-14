<?php

namespace App\Enums;

/**
 * The stages of a main diagnosis and the 04 status each one means (17 · 17.5).
 */
enum MainDiagnosisStage: string
{
    case Draft = 'draft';
    case Invited = 'invited';
    case Filling = 'filling';
    case Running = 'running';
    case Reading = 'reading';
    case Published = 'published';
    case Closed = 'closed';

    public function label(): string
    {
        return __('enums.main_diagnosis_stage.'.$this->value);
    }

    /**
     * Closing is possible from every stage (plan 3.6); withdrawing a publication (21) goes back to reading.
     *
     * @return list<self>
     */
    public function nextStages(): array
    {
        return match ($this) {
            self::Draft => [self::Invited, self::Closed],
            self::Invited => [self::Filling, self::Running, self::Closed],
            self::Filling => [self::Running, self::Closed],
            self::Running => [self::Reading, self::Closed],
            self::Reading => [self::Published, self::Closed],
            self::Published => [self::Reading, self::Closed],
            self::Closed => [],
        };
    }

    public function canTransitionTo(self $stage): bool
    {
        return in_array($stage, $this->nextStages(), true);
    }

    /**
     * The 04 status of the diagnosis in this stage. A diagnosis closed before
     * reading keeps its Draft status (plan 3.6).
     */
    public function status(): ?DiagnosisStatus
    {
        return match ($this) {
            self::Draft, self::Invited, self::Filling, self::Running => DiagnosisStatus::Draft,
            self::Reading => DiagnosisStatus::PendingApproval,
            self::Published => DiagnosisStatus::Published,
            self::Closed => null,
        };
    }

    /**
     * From the invitation until reading starts: subdiagnoses join, leader and
     * peers fill in and upload (17 · 17.4, 17.5).
     */
    public function acceptsInput(): bool
    {
        return in_array($this, [self::Invited, self::Filling, self::Running], true);
    }

    /**
     * The leader fills in the steps (V1–V9); after "Indul" the diagnosis runs.
     */
    public function isBeforeRunning(): bool
    {
        return in_array($this, [self::Draft, self::Invited, self::Filling], true);
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::Published, self::Closed], true);
    }
}
