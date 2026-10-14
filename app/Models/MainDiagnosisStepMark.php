<?php

namespace App\Models;

use App\Enums\DiagnosisStep;
use App\Enums\StepMark;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A step the user viewed (read-only steps) or skipped (processes) (23 · 23.3).
 */
#[Fillable(['main_diagnosis_id', 'user_id', 'step', 'mark'])]
class MainDiagnosisStepMark extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'step' => DiagnosisStep::class,
            'mark' => StepMark::class,
        ];
    }

    /**
     * @return BelongsTo<MainDiagnosis, $this>
     */
    public function mainDiagnosis(): BelongsTo
    {
        return $this->belongsTo(MainDiagnosis::class);
    }
}
