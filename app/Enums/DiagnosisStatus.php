<?php

namespace App\Enums;

/**
 * Draft → Jóváhagyásra vár → Publikált; a published diagnosis can be
 * withdrawn to Draft (04 · 4.3). Users only see published ones.
 */
enum DiagnosisStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Published = 'published';

    public function label(): string
    {
        return __('enums.diagnosis_status.'.$this->value);
    }

    /**
     * @return list<self>
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::Draft => [self::PendingApproval],
            self::PendingApproval => [self::Published, self::Draft],
            self::Published => [self::Draft],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->nextStatuses(), true);
    }

    /**
     * A published diagnosis is read-only until it is withdrawn.
     */
    public function isEditable(): bool
    {
        return $this !== self::Published;
    }
}
