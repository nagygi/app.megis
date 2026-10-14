<?php

use App\Enums\CheckStatus;
use App\Services\Checks\AiConfigurationCheck;

beforeEach(function () {
    config([
        'ai.prompts_path' => base_path('tests/Fixtures/prompts'),
        'ai.tasks' => [
            'mirror_detection' => [
                'provider' => 'anthropic',
                'model' => 'claude-test',
                'prompt' => 'teszt-feladat',
                'prompt_version' => 2,
            ],
            'conversation_summary' => [
                'provider' => 'anthropic',
                'model' => 'claude-test',
                'prompt' => 'beszelgetes-kivonat',
                'prompt_version' => null,
            ],
        ],
        'ai.providers.anthropic.key' => 'sk-test',
        'ai.prices' => ['claude-test' => ['input' => 1.0, 'output' => 5.0]],
    ]);
});

it('passes when every runnable task has its prompt, key and price', function () {
    expect(app(AiConfigurationCheck::class)->run()->status)->toBe(CheckStatus::Ok);
});

it('warns about a missing key outside production', function () {
    config(['ai.providers.anthropic.key' => null]);

    $result = app(AiConfigurationCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Warning)
        ->and($result->message)->toContain('nincs API-kulcsa');
});

it('reports a missing prompt file as an error in production', function () {
    $this->app['env'] = 'production';
    config(['ai.tasks.mirror_detection.prompt_version' => 9]);

    expect(app(AiConfigurationCheck::class)->run()->status)->toBe(CheckStatus::Error);
});

it('warns when a model has no price', function () {
    config(['ai.prices' => []]);

    $result = app(AiConfigurationCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Warning)
        ->and($result->message)->toContain('claude-test');
});

it('is fine while no task has a prompt yet', function () {
    config(['ai.tasks.mirror_detection.prompt_version' => null]);

    expect(app(AiConfigurationCheck::class)->run()->message)->toBe('Még egyik AI-feladatnak sincs aktív promptja.');
});
