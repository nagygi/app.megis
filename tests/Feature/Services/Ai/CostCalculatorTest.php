<?php

use App\Models\AiModelPrice;
use App\Services\Ai\CostCalculator;

it('prices a model whose name has dots', function () {
    config(['ai.prices' => ['gemini-3.8-flash' => ['input' => 0.75, 'output' => 3.75]]]);

    $cost = app(CostCalculator::class)->cost('gemini-3.8-flash', 1_000_000, 100_000);

    expect($cost)->toBe(1.125);
});

it('prices a model from the synced price list', function () {
    config(['ai.prices' => []]);
    AiModelPrice::factory()->create([
        'model' => 'gemini-3.5-flash-lite',
        'provider' => 'gemini',
        'input' => 0.3,
        'output' => 2.5,
        'cache_read' => 0.03,
        'cache_write' => null,
    ]);

    $cost = app(CostCalculator::class)->cost('gemini-3.5-flash-lite', 2_000_000, 1_000_000, cacheReadTokens: 1_000_000);

    expect($cost)->toBe(2.83);
});

it('prefers the price in the config to the synced one', function () {
    config(['ai.prices' => ['claude-sonnet-5-5' => ['input' => 1.0, 'output' => 1.0]]]);
    AiModelPrice::factory()->create(['model' => 'claude-sonnet-5-5', 'input' => 2.0, 'output' => 10.0]);

    $cost = app(CostCalculator::class)->cost('claude-sonnet-5-5', 1_000_000, 1_000_000);

    expect($cost)->toBe(2.0);
});
