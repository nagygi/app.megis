<?php

namespace App\Services\Inbox;

use App\Enums\InboxItemType;
use App\Models\Diagnosis;
use App\Models\InboxItem;
use App\Models\Material;
use App\Models\MirrorSession;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Opens and closes Beérkező rows (22). An open row with the same key is not
 * opened again, and the key closes the row when its cause goes away. A row of
 * the daily scan that an admin closed by hand stays closed while its cause
 * lasts (22 · 22.1).
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
        bool $staysClosed = false,
    ): InboxItem {
        if ($dedupeKey !== null) {
            $existing = InboxItem::query()->open()->where('dedupe_key', $dedupeKey)->first()
                ?? ($staysClosed ? InboxItem::query()->closed()->where('close_reason', InboxItem::CLOSED_MANUALLY)->where('dedupe_key', $dedupeKey)->latest('id')->first() : null);

            if ($existing !== null) {
                return $existing;
            }
        }

        $item = new InboxItem([
            'type' => $type,
            // The Értéktükör's rows go under Értékmérések (06, 22).
            'is_measurement' => $type === InboxItemType::MirrorBooking
                || $subject instanceof MirrorSession
                || ($subject instanceof Material && $subject->context->isMirror()),
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
    public function closeAutomatically(string ...$dedupeKeys): int
    {
        return InboxItem::query()->open()->whereIn('dedupe_key', $dedupeKeys)->update([
            'closed_at' => now(),
            'close_reason' => InboxItem::CLOSED_AUTOMATICALLY,
            'updated_at' => now(),
        ]);
    }

    /**
     * The cause of a row closed by hand went away: the key is free again, so
     * the row can come back when the cause does (22 · 22.1).
     */
    public function release(InboxItem $item): void
    {
        $item->forceFill(['dedupe_key' => null])->save();
    }

    /**
     * The open rows the list shows the admin by default ("alapból: én", 22 · 22.1).
     */
    public function openCount(User $admin): int
    {
        return InboxItem::query()->open()->withoutMeasurements()->forContact($admin)->count();
    }

    public function openMeasurementCount(User $admin): int
    {
        return InboxItem::query()->open()->measurements()->forContact($admin)->count();
    }
}
