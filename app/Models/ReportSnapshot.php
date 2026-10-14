<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The report frozen at publishing and at each correction after it (21 · 21.3,
 * 21.4): the report, the PDF and the Útravaló read from the latest one.
 */
#[Fillable(['main_diagnosis_id', 'sequence', 'reason', 'note', 'data', 'created_by'])]
class ReportSnapshot extends Model
{
    public const UPDATED_AT = null;

    public const PUBLISHED = 'published';

    public const CORRECTION = 'correction';

    public const REPUBLISHED = 'republished';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
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
