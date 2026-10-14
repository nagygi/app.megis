<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A trace of a diagnosis row: a quote or observation, its source, the role
 * and date; the name only for the admin (18 · 18.2).
 */
#[Fillable(['diagnosis_row_id', 'material_id', 'process_occasion_id', 'raw_detection_id', 'quote', 'source_type', 'role', 'user_id', 'recorded_on'])]
class EvidenceTrace extends Model
{
    public const SOURCE_CONVERSATION = 'conversation';

    public const SOURCE_RECORDING = 'recording';

    public const SOURCE_NOTE = 'note';

    public const SOURCE_DOCUMENT = 'document';

    public const SOURCE_MIRROR = 'mirror';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recorded_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<DiagnosisRow, $this>
     */
    public function row(): BelongsTo
    {
        return $this->belongsTo(DiagnosisRow::class, 'diagnosis_row_id');
    }

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * @return BelongsTo<ProcessOccasion, $this>
     */
    public function occasion(): BelongsTo
    {
        return $this->belongsTo(ProcessOccasion::class, 'process_occasion_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
