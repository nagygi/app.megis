<?php

namespace App\Models;

use Database\Factories\CapabilityStoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The text of a user on the Képességek step (NM-12), one per main diagnosis:
 * the question asked, the situations typed, spoken or uploaded, and once sent
 * the material the capability measurement reads against the Képességtár.
 * The user does not score; the situations do.
 */
#[Fillable(['main_diagnosis_id', 'user_id', 'question', 'answer', 'material_id', 'submitted_at'])]
class CapabilityStory extends Model
{
    /** @use HasFactory<CapabilityStoryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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
