<?php

namespace App\Models;

use App\Enums\AiCallStatus;
use Database\Factories\AiCallFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Log row of one AI call: model, prompt version, tokens and cost (16 · 16.3, 16.4).
 * Every AI result refers to its row. Written once.
 */
#[Fillable([
    'task', 'provider', 'model', 'model_version', 'prompt', 'prompt_version', 'status', 'error',
    'input_tokens', 'output_tokens', 'cache_read_tokens', 'cache_write_tokens', 'audio_minutes',
    'organization_id', 'organization_name', 'public_mirror', 'cost_usd', 'duration_ms',
])]
class AiCall extends Model
{
    /** @use HasFactory<AiCallFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AiCallStatus::class,
            'prompt_version' => 'integer',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'cache_read_tokens' => 'integer',
            'cache_write_tokens' => 'integer',
            'audio_minutes' => 'float',
            'public_mirror' => 'boolean',
            'cost_usd' => 'float',
            'duration_ms' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function totalTokens(): int
    {
        return $this->input_tokens + $this->output_tokens;
    }

    /**
     * "Nyilvános Értéktükör" for the public mirror (16 · 16.4).
     */
    public function contextLabel(): string
    {
        return $this->public_mirror
            ? __('admin.ai.public_mirror')
            : ($this->organization?->name ?? $this->organization_name ?? __('admin.common.none'));
    }
}
