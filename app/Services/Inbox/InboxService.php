<?php

namespace App\Services\Inbox;

use App\Enums\InboxItemType;
use App\Models\Diagnosis;
use App\Models\InboxItem;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Opens and closes Beérkező rows (22). An open row with the same key is not
 * opened again, and the key closes the row when its cause goes away.
 */
class InboxService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function open(
        InboxItemType $type,
        string $summary,
        ?Organization $organization = null,
        ?Diagnosis $diagnosis = null,
        ?Model $subject = null,
        array $payload = [],
        ?string $dedupeKey = null,
    ): InboxItem {
        if ($dedupeKey !== null) {
            $existing = InboxItem::query()->open()->where('dedupe_key', $dedupeKey)->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        $item = new InboxItem([
            'type' => $type,
            'organization_id' => $organization?->id ?? $diagnosis?->organization_id,
            'diagnosis_id' => $diagnosis?->id,
            'summary' => mb_substr($summary, 0, 500),
            'payload' => $payload === [] ? null : $payload,
            'dedupe_key' => $dedupeKey,
        ]);
        $item->subject()->associate($subject);
        $item->save();

        return $item;
    }

    /**
     * Any admin may close a row (22 · 22.1).
     */
    public function close(InboxItem $item, ?User $closedBy = null, string $reason = InboxItem::CLOSED_MANUALLY): void
    {
        if (! $item->isOpen()) {
            return;
        }

        $item->forceFill([
            'closed_at' => now(),
            'closed_by' => $closedBy?->id,
            'close_reason' => $reason,
        ])->save();
    }

    /**
     * Closes the open row of a cause that went away, e.g. the threshold was met.
     */
    public function closeAutomatically(string $dedupeKey): int
    {
        return InboxItem::query()->open()->where('dedupe_key', $dedupeKey)->update([
            'closed_at' => now(),
            'close_reason' => InboxItem::CLOSED_AUTOMATICALLY,
            'updated_at' => now(),
        ]);
    }

    public function openCount(): int
    {
        return InboxItem::query()->open()->count();
    }
}
