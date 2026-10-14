<?php

namespace App\Models;

use App\Enums\ProcessConfidence;
use App\Enums\ProcessKind;
use App\Enums\ProcessRhythm;
use App\Enums\ProcessSource;
use App\Models\Concerns\Blameable;
use Database\Factories\ProcessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A recurring process of the organization (11), detected from the calendar
 * (09), the conversation (10) or added by hand. A diagnosis records the
 * processes it measures.
 */
#[Fillable([
    'organization_id', 'parent_id', 'name', 'rhythm', 'rhythm_label', 'duration_minutes', 'headcount',
    'occurrence_label', 'kind', 'source', 'confidence', 'unclear', 'accepted_at', 'responsible_user_id',
    'breakpoint_candidate', 'calendar_series_key', 'merge_candidate_id',
])]
class Process extends Model
{
    /** @use HasFactory<ProcessFactory> */
    use Blameable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rhythm' => ProcessRhythm::class,
            'kind' => ProcessKind::class,
            'source' => ProcessSource::class,
            'confidence' => ProcessConfidence::class,
            'unclear' => 'boolean',
            'breakpoint_candidate' => 'boolean',
            'accepted_at' => 'datetime',
            'duration_minutes' => 'integer',
            'headcount' => 'integer',
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
     * @return BelongsTo<Process, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Process::class, 'parent_id');
    }

    /**
     * @return HasMany<Process, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Process::class, 'parent_id')->orderBy('name');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /**
     * @return HasMany<ProcessRename, $this>
     */
    public function renames(): HasMany
    {
        return $this->hasMany(ProcessRename::class)->latest('id');
    }

    /**
     * @return HasMany<ProcessParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ProcessParticipant::class)->orderBy('id');
    }

    /**
     * @return BelongsToMany<Team, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class);
    }

    /**
     * @return HasMany<ProcessMetric, $this>
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(ProcessMetric::class)->orderBy('id');
    }

    /**
     * @return HasMany<ProcessOccasion, $this>
     */
    public function occasions(): HasMany
    {
        return $this->hasMany(ProcessOccasion::class)->orderBy('occurred_at');
    }

    /**
     * @return BelongsTo<Process, $this>
     */
    public function mergeCandidate(): BelongsTo
    {
        return $this->belongsTo(Process::class, 'merge_candidate_id');
    }

    /**
     * Accepted routines on the top level: what a diagnosis measures (11 · 11.4).
     */
    #[Scope]
    protected function measured(Builder $query): void
    {
        $query->whereNull('parent_id')->where('kind', ProcessKind::Routine)->whereNotNull('accepted_at');
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isInCalendar(): bool
    {
        return $this->source === ProcessSource::Calendar;
    }

    /**
     * "keddenként 9:00 · 4 fő · 8/12 hét" on the card (11 · 11.2).
     */
    public function summaryLine(): string
    {
        return collect([
            $this->rhythm_label ?? $this->rhythm?->label(),
            $this->headcount ? __('processes.headcount', ['count' => $this->headcount]) : null,
            $this->occurrence_label,
        ])->filter()->implode(' · ');
    }
}
