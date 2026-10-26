<?php

namespace App\Models;

use Database\Factories\CapabilityStoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A situation told to a value on the Képességek step (NM-12): the question
 * asked, the answer typed or spoken, and once sent the material the
 * capability measurement reads. The user does not score; the situation does.
 */
#[Fillable([
    'main_diagnosis_id', 'user_id', 'value_source', 'value_key', 'value_name', 'value_meaning',
    'kind', 'question', 'answer', 'material_id', 'position', 'submitted_at',
])]
class CapabilityStory extends Model
{
    /** @use HasFactory<CapabilityStoryFactory> */
    use HasFactory;

    public const SOURCE_OWN = 'own';

    public const SOURCE_ORGANIZATION = 'organization';

    /** The value was tested: when did it cost something? */
    public const KIND_TESTED = 'tested';

    /** The value was missed: when should it have been there, and was not? */
    public const KIND_MISSED = 'missed';

    /** An own value: when did you hold to it? */
    public const KIND_OWN = 'own';

    public const KINDS = [self::KIND_TESTED, self::KIND_MISSED, self::KIND_OWN];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'submitted_at' => 'datetime',
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

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    /**
     * Whether the answer is long enough to tell a situation.
     */
    public function hasAnswer(): bool
    {
        return mb_strlen(trim((string) $this->answer)) >= (int) config('diagnosis.capability_stories.min_characters');
    }
}
