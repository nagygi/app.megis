<?php

namespace App\Models;

use App\Enums\InboxItemType;
use Database\Factories\InboxItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A row of the Beérkező: one decision, at most two buttons (22).
 */
#[Fillable(['type', 'is_measurement', 'organization_id', 'diagnosis_id', 'summary', 'payload', 'dedupe_key'])]
class InboxItem extends Model
{
    /** @use HasFactory<InboxItemFactory> */
    use HasFactory;

    public const CLOSED_MANUALLY = 'manual';

    public const CLOSED_AUTOMATICALLY = 'automatic';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => InboxItemType::class,
            'is_measurement' => 'boolean',
            'payload' => 'array',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Diagnosis, $this>
     */
    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * @param  Builder<InboxItem>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereNull('closed_at');
    }

    /**
     * @param  Builder<InboxItem>  $query
     */
    #[Scope]
    protected function closed(Builder $query): void
    {
        $query->whereNotNull('closed_at');
    }

    /**
     * The Értéktükör rows: bookings and runs that failed to process (06, 22).
     * They are listed under Értékmérések.
     *
     * @param  Builder<InboxItem>  $query
     */
    #[Scope]
    protected function measurements(Builder $query): void
    {
        $query->where('is_measurement', true);
    }

    /**
     * The Beérkező itself: every row but the Értéktükör's.
     *
     * @param  Builder<InboxItem>  $query
     */
    #[Scope]
    protected function withoutMeasurements(Builder $query): void
    {
        $query->where('is_measurement', false);
    }

    /**
     * Rows the admin looks after: organizations where they are the contact,
     * and rows nobody in particular looks after (22 · 22.1, "alapból: én").
     *
     * @param  Builder<InboxItem>  $query
     */
    #[Scope]
    protected function forContact(Builder $query, User $admin): void
    {
        $query->where(fn (Builder $mine) => $mine
            ->whereNull('organization_id')
            ->orWhereHas('organization', fn (Builder $organization) => $organization
                ->where('contact_admin_id', $admin->id)
                ->orWhereNull('contact_admin_id')));
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }

    /**
     * The customer the row is about, whose datasheet it links (33 · 33.1):
     * the invitee, the leader, the one who gave feedback, booked or uploaded.
     */
    public function subjectUser(): ?User
    {
        return match (true) {
            $this->subject instanceof MainDiagnosis => $this->subject->leader,
            $this->subject instanceof MainDiagnosisParticipant,
            $this->subject instanceof ReportFeedback,
            $this->subject instanceof MirrorSession,
            $this->subject instanceof Material => $this->subject->user,
            default => null,
        };
    }
}
