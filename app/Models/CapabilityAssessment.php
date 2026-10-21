<?php

namespace App\Models;

use App\Enums\CapabilityConfidence;
use App\Enums\CapabilityLevel;
use App\Enums\CapabilityState;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The state of a capability at a level in a diagnosis (NM-12, 6.2, 6.3):
 * képesség · alany · időszak · állapot · konfidencia · nyomok · akadály.
 * The automatic values follow the findings; an override keeps the final ones.
 */
#[Fillable([
    'diagnosis_id', 'capability_id', 'capability_version_id', 'subject_level', 'automatic_state', 'automatic_confidence',
    'automatic_basis', 'state', 'confidence', 'uneven', 'blocker_precondition_id', 'blocker_note', 'override_reason',
    'overridden_by', 'overridden_at', 'approved_by', 'approved_at', 'created_by',
])]
class CapabilityAssessment extends Model
{
    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'automatic_basis' => '{}',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subject_level' => CapabilityLevel::class,
            'automatic_state' => CapabilityState::class,
            'automatic_confidence' => CapabilityConfidence::class,
            'automatic_basis' => 'array',
            'state' => CapabilityState::class,
            'confidence' => CapabilityConfidence::class,
            'uneven' => 'boolean',
            'overridden_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Diagnosis, $this>
     */
    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class);
    }

    /**
     * @return BelongsTo<Capability, $this>
     */
    public function capability(): BelongsTo
    {
        return $this->belongsTo(Capability::class);
    }

    /**
     * @return BelongsTo<Precondition, $this>
     */
    public function blocker(): BelongsTo
    {
        return $this->belongsTo(Precondition::class, 'blocker_precondition_id');
    }

    public function isOverridden(): bool
    {
        return $this->overridden_at !== null;
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }
}
