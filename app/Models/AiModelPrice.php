<?php

namespace App\Models;

use Database\Factories\AiModelPriceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Unit prices of one model from the public price list, refreshed daily by
 * megis:prices:sync (16 · 16.4). A price in config/ai.php overrides it.
 */
#[Fillable(['model', 'provider', 'input', 'output', 'cache_read', 'cache_write', 'audio_minute', 'source_key', 'synced_at'])]
class AiModelPrice extends Model
{
    /** @use HasFactory<AiModelPriceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'input' => 'float',
            'output' => 'float',
            'cache_read' => 'float',
            'cache_write' => 'float',
            'audio_minute' => 'float',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * The shape of a config/ai.php price; a missing cache price falls back to the input price.
     *
     * @return array{input: float, output: float, cache_read?: float, cache_write?: float, audio_minute?: float}
     */
    public function toPriceArray(): array
    {
        return array_filter([
            'input' => $this->input,
            'output' => $this->output,
            'cache_read' => $this->cache_read,
            'cache_write' => $this->cache_write,
            'audio_minute' => $this->audio_minute,
        ], fn (?float $price) => $price !== null);
    }
}
