<?php

namespace App\Models;

use App\Enums\PreconditionValue;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A precondition (E1–E9) in a diagnosis (6.7): megvan / részben / nincs /
 * nem tudjuk, from the traces or set by an admin. Not scored.
 */
#[Fillable(['diagnosis_id', 'precondition_id', 'automatic_value', 'value', 'note', 'overridden_by', 'overridden_at', 'approved_by', 'approved_at'])]
class CapabilityPreconditionAssessment extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'automatic_value' => PreconditionValue::class,
            'value' => PreconditionValue::class,
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
     * @return BelongsTo<Precondition, $this>
     */
    public function precondition(): BelongsTo
    {
        return $this->belongsTo(Precondition::class);
    }

    public function isOverridden(): bool
    {
        return $this->overridden_at !== null;
    }
}
