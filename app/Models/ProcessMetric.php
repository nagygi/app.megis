<?php

namespace App\Models;

use App\Enums\MetricRhythm;
use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An own measure of a process, recorded with a date; not part of the PHI (11 · 11.3).
 */
#[Fillable(['process_id', 'name', 'unit', 'rhythm', 'target', 'description'])]
class ProcessMetric extends Model
{
    use Blameable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rhythm' => MetricRhythm::class,
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
     * The latest value first (11 · 11.3).
     *
     * @return HasMany<ProcessMetricValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(ProcessMetricValue::class)->orderByDesc('measured_on')->orderByDesc('id');
    }
}
