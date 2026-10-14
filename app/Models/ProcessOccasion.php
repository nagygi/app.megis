<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An occasion of a process: from the calendar series (09 · 9.4) or given at
 * upload (12). Its date stays when the calendar metadata is deleted (09 · 9.5).
 */
#[Fillable(['process_id', 'calendar_event_id', 'calendar_event_key', 'occurred_at', 'duration_minutes', 'participant_count', 'source', 'created_by'])]
class ProcessOccasion extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Process, $this>
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    /**
     * The uploads and situations of the occasion (12 · 12.2).
     *
     * @return HasMany<Material, $this>
     */
    public function materials(): HasMany
    {
        return $this->hasMany(Material::class, 'process_occasion_id');
    }

    /**
     * @return HasMany<OccasionExperience, $this>
     */
    public function experiences(): HasMany
    {
        return $this->hasMany(OccasionExperience::class, 'process_occasion_id');
    }
}
