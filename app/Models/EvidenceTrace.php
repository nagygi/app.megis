<?php

namespace App\Models;

use App\Enums\MaterialContext;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A trace of a diagnosis row: a quote or observation, its source, the role
 * and date; the name only for the admin (18 · 18.2).
 */
#[Fillable(['diagnosis_row_id', 'material_id', 'process_occasion_id', 'raw_detection_id', 'quote', 'source_type', 'role', 'user_id', 'recorded_on', 'excluded_at', 'highlighted_at', 'created_by'])]
class EvidenceTrace extends Model
{
    public const SOURCE_CONVERSATION = 'conversation';

    public const SOURCE_RECORDING = 'recording';

    public const SOURCE_NOTE = 'note';

    public const SOURCE_DOCUMENT = 'document';

    public const SOURCE_MIRROR = 'mirror';

    public const SOURCE_CALENDAR = 'calendar';

    public const SOURCE_EXPERIENCE = 'experience';

    public const SOURCES = [self::SOURCE_CONVERSATION, self::SOURCE_RECORDING, self::SOURCE_NOTE, self::SOURCE_DOCUMENT, self::SOURCE_MIRROR, self::SOURCE_CALENDAR, self::SOURCE_EXPERIENCE];

    public const ROLES = ['leader', 'peer', 'external'];

    /**
     * Where a quote of the material comes from, never whose (18 · 18.2).
     */
    public static function sourceTypeFor(Material $material): string
    {
        return match (true) {
            $material->context->isMirror() => self::SOURCE_MIRROR,
            $material->is_situation => self::SOURCE_NOTE,
            $material->context === MaterialContext::AdminUpload => self::SOURCE_CONVERSATION,
            $material->source->hasAudio() => self::SOURCE_RECORDING,
            default => self::SOURCE_DOCUMENT,
        };
    }

    public function isIncluded(): bool
    {
        return $this->excluded_at === null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recorded_on' => 'date',
            'excluded_at' => 'datetime',
            'highlighted_at' => 'datetime',
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
