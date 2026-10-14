<?php

namespace App\Models;

use App\Enums\DiagnosisStatus;
use App\Enums\MainDiagnosisStage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A status change of a diagnosis, who and when (04 · 4.3); for a main
 * diagnosis also its stage (17 · 17.5). Written once.
 */
#[Fillable(['from_status', 'to_status', 'from_stage', 'to_stage', 'created_by', 'reason'])]
class DiagnosisTransition extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => DiagnosisStatus::class,
            'to_status' => DiagnosisStatus::class,
            'from_stage' => MainDiagnosisStage::class,
            'to_stage' => MainDiagnosisStage::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
