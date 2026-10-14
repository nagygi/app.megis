<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A detection matched to an Értéktár value with a confidence (06 · 6.4, step B).
 */
#[Fillable(['value_id', 'value_version_id', 'confidence', 'ai_call_id'])]
class DetectionMatch extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confidence' => 'float',
        ];
    }

    /**
     * @return BelongsTo<RawDetection, $this>
     */
    public function detection(): BelongsTo
    {
        return $this->belongsTo(RawDetection::class, 'raw_detection_id');
    }

    /**
     * @return BelongsTo<Value, $this>
     */
    public function value(): BelongsTo
    {
        return $this->belongsTo(Value::class);
    }
}
