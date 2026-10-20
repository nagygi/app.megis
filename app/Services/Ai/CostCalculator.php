<?php

namespace App\Services\Ai;

use App\Models\AiModelPrice;

/**
 * USD cost of a call from the unit prices (16 · 16.4): a price in
 * config/ai.php first, then the one megis:prices:sync keeps up to date.
 * Cached input tokens are priced apart when the model has such a price.
 */
class CostCalculator
{
    public function hasPrice(string $model): bool
    {
        return $this->price($model) !== null;
    }

    /**
     * The model name is a plain array key: config('ai.prices.'.$model) would
     * split "gemini-3.8-flash" at its dots.
     *
     * @return array{input?: float, output?: float, cache_read?: float, cache_write?: float, audio_minute?: float}|null
     */
    public function price(string $model): ?array
    {
        $configured = (config('ai.prices') ?? [])[$model] ?? null;

        if (is_array($configured)) {
            return $configured;
        }

        return AiModelPrice::query()->where('model', $model)->first()?->toPriceArray();
    }

    /**
     * Null when the model has no price.
     */
    public function cost(
        string $model,
        int $inputTokens,
        int $outputTokens,
        int $cacheReadTokens = 0,
        int $cacheWriteTokens = 0,
        ?float $audioMinutes = null,
    ): ?float {
        $prices = $this->price($model);

        if ($prices === null) {
            return null;
        }

        $input = (float) ($prices['input'] ?? 0);
        $cacheRead = (float) ($prices['cache_read'] ?? $input);
        $cacheWrite = (float) ($prices['cache_write'] ?? $input);
        $uncachedInput = max(0, $inputTokens - $cacheReadTokens - $cacheWriteTokens);

        $cost = ($uncachedInput * $input
            + $cacheReadTokens * $cacheRead
            + $cacheWriteTokens * $cacheWrite
            + $outputTokens * (float) ($prices['output'] ?? 0)) / 1_000_000;

        if ($audioMinutes !== null) {
            $cost += $audioMinutes * (float) ($prices['audio_minute'] ?? 0);
        }

        return round($cost, 6);
    }
}
