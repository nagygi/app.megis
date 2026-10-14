<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A value the engine found in a material, raw (06 · 6.4, step A).
 */
#[Fillable(['ai_call_id', 'speaker', 'quote', 'free_name', 'reasoning', 'strength', 'excluded_at', 'unmatched_value_id'])]
class RawDetection extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'strength' => 'integer',
            'excluded_at' => 'datetime',
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
     * @return BelongsTo<AiCall, $this>
     */
    public function aiCall(): BelongsTo
    {
        return $this->belongsTo(AiCall::class);
    }

    /**
     * @return HasMany<DetectionMatch, $this>
     */
    public function matches(): HasMany
    {
        return $this->hasMany(DetectionMatch::class)->orderByDesc('confidence');
    }

    /**
     * The "AI által detektált" value it became when nothing matched.
     *
     * @return BelongsTo<Value, $this>
     */
    public function unmatchedValue(): BelongsTo
    {
        return $this->belongsTo(Value::class, 'unmatched_value_id');
    }

    /**
     * Not marked "nem az enyém".
     *
     * @param  Builder<RawDetection>  $query
     */
    #[Scope]
    protected function kept(Builder $query): void
    {
        $query->whereNull('excluded_at');
    }

    public function isExcluded(): bool
    {
        return $this->excluded_at !== null;
    }
}
