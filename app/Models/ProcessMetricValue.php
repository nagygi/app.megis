<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One recorded value of an own measure, by the responsible, the leader or an admin (11 · 11.3).
 */
#[Fillable(['process_metric_id', 'value', 'measured_on', 'created_by'])]
class ProcessMetricValue extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'measured_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function valueLabel(): string
    {
        $value = (float) $this->value;

        return fmod($value, 1.0) === 0.0 ? (string) (int) $value : number_format($value, 2, ',', '');
    }
}
