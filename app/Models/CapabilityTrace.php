<?php

namespace App\Models;

use App\Enums\CapabilityLevel;
use App\Enums\TraceCandidate;
use App\Enums\TraceDirection;
use App\Enums\TraceDirectness;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A trace (NM-12, 6.1): a verbatim part of a material that may show a
 * capability or a precondition, with who said it, at what level, in which
 * direction and how directly. Client data: it goes with its material.
 */
#[Fillable([
    'organization_id', 'material_id', 'ai_call_id', 'speaker', 'speaker_role', 'quote', 'location',
    'subject_level', 'boundary', 'direction', 'directness', 'is_raw', 'flags', 'preconditions',
    'suggested_capability_ids', 'detector_blocks', 'alternative_reading', 'candidate', 'candidate_note',
    'recorded_on', 'excluded_at', 'excluded_by', 'created_by',
])]
class CapabilityTrace extends Model
{
    /**
     * Marks that make a trace weak: it only confirms, it never founds on its own (6.1).
     */
    public const FLAGS = [
        'led_answer', 'third_person', 'old_event', 'self_presentation', 'absence_trace',
        'positive_prompt', 'echoed_opinion', 'audit_report', 'ai_corrected',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'flags' => '[]',
        'preconditions' => '[]',
        'suggested_capability_ids' => '[]',
        'detector_blocks' => '[]',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subject_level' => CapabilityLevel::class,
            'boundary' => 'boolean',
            'direction' => TraceDirection::class,
            'directness' => TraceDirectness::class,
            'is_raw' => 'boolean',
            'flags' => 'array',
            'preconditions' => 'array',
            'suggested_capability_ids' => 'array',
            'detector_blocks' => 'array',
            'candidate' => TraceCandidate::class,
            'recorded_on' => 'date',
            'excluded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * @return BelongsToMany<CapabilityFinding, $this>
     */
    public function findings(): BelongsToMany
    {
        return $this->belongsToMany(CapabilityFinding::class, 'capability_finding_trace');
    }

    /**
     * Not excluded by an admin and not blocked by a detector-error pattern.
     *
     * @param  Builder<CapabilityTrace>  $query
     */
    #[Scope]
    protected function usable(Builder $query): void
    {
        $query->whereNull('excluded_at');
    }

    public function isBlocked(): bool
    {
        return $this->detector_blocks !== [];
    }

    public function isUsable(): bool
    {
        return $this->excluded_at === null && ! $this->isBlocked();
    }
}
