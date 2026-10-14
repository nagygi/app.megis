<?php

namespace App\Services\Ai;

/**
 * USD cost of a call from the unit prices in config/ai.php (16 · 16.4).
 * Cached input tokens are priced apart when the model has such a price.
 */
class CostCalculator
{
    public function hasPrice(string $model): bool
    {
        return is_array(config('ai.prices.'.$model));
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
        $prices = config('ai.prices.'.$model);

        if (! is_array($prices)) {
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
