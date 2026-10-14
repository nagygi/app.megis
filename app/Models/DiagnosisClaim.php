<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A sentence of the report about a row, with its traces (18 · 18.3):
 * strength, crack ("töréspont" in a snapshot) or hunch. AI draft first;
 * only approved claims reach the report (20).
 */
#[Fillable(['diagnosis_id', 'diagnosis_row_id', 'kind', 'text', 'status', 'trace_ids', 'ai_call_id'])]
class DiagnosisClaim extends Model
{
    use Blameable;

    public const KINDS = ['strength', 'crack', 'hunch'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trace_ids' => 'array',
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
