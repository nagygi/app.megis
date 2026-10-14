<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "Magatokra ismertek?" on a chapter (28 · 28.1): Igen, Részben or Nem így
 * látjuk, with text or a spoken answer. The latest one counts.
 */
#[Fillable(['main_diagnosis_id', 'user_id', 'chapter', 'answer', 'text', 'material_id', 'incorporated_at', 'incorporated_by', 'answered_at'])]
class ReportFeedback extends Model
{
    protected $table = 'report_feedback';

    public const ANSWERS = ['yes', 'partial', 'no'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'incorporated_at' => 'datetime',
            'answered_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MainDiagnosis, $this>
     */
    public function mainDiagnosis(): BelongsTo
    {
        return $this->belongsTo(MainDiagnosis::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
