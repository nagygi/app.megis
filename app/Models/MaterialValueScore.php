<?php

namespace App\Models;

use App\Enums\ScoreResultState;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Step C for one matched value and speaker: 1–6 with the quotes behind it,
 * or "nincs adat" (06 · 6.4).
 */
#[Fillable(['speaker', 'value_id', 'value_version_id', 'state', 'score', 'reasoning', 'detection_ids', 'ai_call_id'])]
class MaterialValueScore extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'state' => ScoreResultState::class,
            'score' => 'float',
            'detection_ids' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * @return BelongsTo<Value, $this>
     */
    public function value(): BelongsTo
    {
        return $this->belongsTo(Value::class);
    }
}
