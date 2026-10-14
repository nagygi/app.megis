<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * A sentence of the report about a row, with its traces (18 · 18.3):
 * strength, crack ("töréspont" in a snapshot) or hunch. AI draft first;
 * only approved claims reach the report (20).
 */
#[Fillable(['diagnosis_id', 'diagnosis_row_id', 'kind', 'text', 'status', 'trace_ids', 'ai_call_id', 'approved_at', 'approved_by'])]
class DiagnosisClaim extends Model
{
    use Blameable;

    public const KINDS = ['strength', 'crack', 'hunch'];

    public const DRAFT = 'draft';

    public const APPROVED = 'approved';

    /**
     * The traces the claim is built on, in the order of the row.
     *
     * @return Collection<int, EvidenceTrace>
     */
    public function traces(): Collection
    {
        return EvidenceTrace::query()->whereIn('id', $this->trace_ids ?? [])->orderBy('recorded_on')->get();
    }

    public function isApproved(): bool
    {
        return $this->status === self::APPROVED;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trace_ids' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DiagnosisRow, $this>
     */
    public function row(): BelongsTo
    {
        return $this->belongsTo(DiagnosisRow::class, 'diagnosis_row_id');
    }
}
