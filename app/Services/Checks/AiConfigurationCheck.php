<?php

namespace App\Services\Checks;

use App\Services\Ai\CostCalculator;
use App\Services\Ai\PromptRepository;

/**
 * Every AI task that can run has its prompt file, its provider key and a
 * unit price (16 · 16.1–16.4). A task without a prompt version is not yet
 * built and is skipped. Missing prompts or keys are errors in production.
 */
class AiConfigurationCheck implements Check
{
    public function __construct(
        private PromptRepository $prompts,
        private CostCalculator $costs,
    ) {}

    public function run(): CheckResult
    {
        $label = __('checks.ai.label');
        $tasks = collect(config('ai.tasks'))->filter(fn (array $task) => $task['prompt_version'] !== null);

        if ($tasks->isEmpty()) {
            return CheckResult::ok($label, __('checks.ai.none_active'));
        }

        $problems = [];
        $missingPrices = [];

        foreach ($tasks as $key => $task) {
            if ($this->prompts->find((string) $task['prompt'], (int) $task['prompt_version']) === null) {
                $problems[] = __('checks.ai.missing_prompt', ['task' => $key, 'prompt' => $task['prompt'].'/v'.$task['prompt_version']]);
            }

            if (! $this->hasCredentials((string) $task['provider'])) {
                $problems[] = __('checks.ai.missing_key', ['task' => $key, 'provider' => $task['provider']]);
            }

            if ($task['model'] === null || ! $this->costs->hasPrice($task['model'])) {
                $missingPrices[] = (string) ($task['model'] ?? $key);
            }
        }

        if ($problems !== []) {
            $message = implode(' ', $problems);

            return app()->isProduction() ? CheckResult::error($label, $message) : CheckResult::warning($label, $message);
        }

        if ($missingPrices !== []) {
            return CheckResult::warning($label, __('checks.ai.missing_price', ['models' => implode(', ', array_unique($missingPrices))]));
        }

        return CheckResult::ok($label, __('checks.ai.ok', ['count' => $tasks->count()]));
    }

    private function hasCredentials(string $provider): bool
    {
        $config = config('ai.providers.'.$provider);

        if (! is_array($config)) {
            return false;
        }

        // Bedrock may sign with the server's AWS role instead of a key.
        if (($config['driver'] ?? null) === 'bedrock') {
            return filled($config['key'] ?? null) || filled($config['access_key_id'] ?? null) || (bool) ($config['use_default_credential_provider'] ?? false);
        }

        return filled($config['key'] ?? null);
    }
}
