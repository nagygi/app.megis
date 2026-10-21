<?php

namespace App\Models;

use App\Enums\CapabilityConfidence;
use App\Enums\CapabilityLevel;
use App\Enums\FindingKind;
use App\Enums\FindingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A finding (NM-12, 6.6): a pattern of a capability in one material at a
 * level, with its traces. The AI proposes it with at most medium machine
 * confidence; an admin confirms or rejects it, a rejection is a false alarm
 * of the pattern (NY-27). Shared by every diagnosis the material is part of.
 */
#[Fillable([
    'organization_id', 'material_id', 'capability_id', 'pattern_id', 'pattern_version_id', 'kind', 'subject_level',
    'status', 'machine_confidence', 'reasoning', 'needs_first_approval', 'ai_call_id', 'reviewed_by', 'reviewed_at', 'review_note',
])]
class CapabilityFinding extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => FindingKind::class,
            'subject_level' => CapabilityLevel::class,
            'status' => FindingStatus::class,
            'machine_confidence' => CapabilityConfidence::class,
            'needs_first_approval' => 'boolean',
            'reviewed_at' => 'datetime',
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
     * @return BelongsTo<Capability, $this>
     */
    public function capability(): BelongsTo
    {
        return $this->belongsTo(Capability::class);
    }

    /**
     * @return BelongsTo<Pattern, $this>
     */
    public function pattern(): BelongsTo
    {
        return $this->belongsTo(Pattern::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return BelongsToMany<CapabilityTrace, $this>
     */
    public function traces(): BelongsToMany
    {
        return $this->belongsToMany(CapabilityTrace::class, 'capability_finding_trace');
    }

    public function isReviewed(): bool
    {
        return $this->reviewed_at !== null;
    }
}
