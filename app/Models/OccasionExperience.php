<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "A megélésed" for one occasion (12 · 12.4): 1–6 or "nem tudom" per row,
 * whether it was effective, a note. Sent or skipped; can be made up later.
 */
#[Fillable(['process_occasion_id', 'user_id', 'main_diagnosis_id', 'material_id', 'answers', 'effectiveness', 'note', 'value_set_version_id', 'submitted_at', 'skipped_at'])]
class OccasionExperience extends Model
{
    public const EFFECTIVENESS = ['yes', 'partial', 'no', 'dont_know'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'submitted_at' => 'datetime',
            'skipped_at' => 'datetime',
        ];
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

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }
}
