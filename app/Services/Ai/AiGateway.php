<?php

namespace App\Services\Ai;

use App\Ai\Agents\StructuredTaskAgent;
use App\Ai\Agents\TaskAgent;
use App\Ai\AiContext;
use App\Ai\AiResult;
use App\Ai\Contracts\AiTask;
use App\Enums\AiCallStatus;
use App\Models\AiCall;
use Closure;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Gateway\FakeTextGateway;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;
use Throwable;

/**
 * The common AI layer (16 · 16.1): picks the provider and model of the task
 * from the configuration, uses the active prompt version, and logs every call
 * with the exact model, tokens and cost (16 · 16.3, 16.4; NFR · 11).
 */
class AiGateway
{
    public function __construct(
        private PromptRepository $prompts,
        private CostCalculator $costs,
    ) {}

    /**
     * Fakes every task in tests: no provider is ever called.
     *
     * @param  Closure|array<int, mixed>  $responses
     * @return list<FakeTextGateway>
     */
    public static function fake(Closure|array $responses = []): array
    {
        return [
            TaskAgent::fake($responses)->preventStrayPrompts(),
            StructuredTaskAgent::fake($responses)->preventStrayPrompts(),
        ];
    }

    /**
     * Runs the task once. A failed call is logged and the error rethrown, so
     * the queue job can retry it.
     */
    public function run(AiTask $task, AiContext $context = new AiContext): AiResult
    {
        $config = config('ai.tasks.'.$task->key());

        if (! is_array($config)) {
            throw new RuntimeException("Unknown AI task [{$task->key()}].");
        }

        $prompt = $this->prompts->activeFor($task->key());
        $agent = $this->agentFor($task, $prompt->render($task->variables()), $config);
        $startedAt = hrtime(true);

        try {
            $response = $agent->prompt(
                $task->input(),
                provider: $config['provider'],
                model: $config['model'],
                timeout: $config['timeout'] ?? null,
            );
        } catch (Throwable $exception) {
            $this->log($task, $context, $config, $prompt, $startedAt, error: $exception);

            throw $exception;
        }

        $call = $this->log($task, $context, $config, $prompt, $startedAt, usage: [
            'input_tokens' => $response->usage->inputTokens,
            'output_tokens' => $response->usage->outputTokens,
            'cache_read_tokens' => $response->usage->cacheReadInputTokens ?? 0,
            'cache_write_tokens' => $response->usage->cacheWriteInputTokens ?? 0,
        ], modelVersion: $response->meta->model);

        return new AiResult(
            text: $response->text,
            structured: $response instanceof StructuredAgentResponse ? $response->structured : null,
            call: $call,
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function agentFor(AiTask $task, string $instructions, array $config): TaskAgent
    {
        $maxTokens = $config['max_tokens'] ?? null;
        $temperature = isset($config['temperature']) ? (float) $config['temperature'] : null;

        // The schema is asked once, here, so a task without one gets a plain text agent.
        $probe = $task->schema(new JsonSchemaTypeFactory);

        return $probe === null
            ? new TaskAgent($instructions, $maxTokens, $temperature)
            : new StructuredTaskAgent($instructions, fn (JsonSchema $schema) => $task->schema($schema), $maxTokens, $temperature);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array{input_tokens: int, output_tokens: int, cache_read_tokens: int, cache_write_tokens: int}|null  $usage
     */
    private function log(
        AiTask $task,
        AiContext $context,
        array $config,
        Prompt $prompt,
        int|float $startedAt,
        ?array $usage = null,
        ?string $modelVersion = null,
        ?Throwable $error = null,
    ): AiCall {
        $usage ??= ['input_tokens' => 0, 'output_tokens' => 0, 'cache_read_tokens' => 0, 'cache_write_tokens' => 0];

        $call = new AiCall([
            'task' => $task->key(),
            'provider' => $config['provider'],
            'model' => $config['model'],
            'model_version' => $modelVersion,
            'prompt' => $prompt->name,
            'prompt_version' => $prompt->version,
            'status' => $error === null ? AiCallStatus::Succeeded : AiCallStatus::Failed,
            'error' => $error === null ? null : mb_substr(class_basename($error).': '.$error->getMessage(), 0, 1000),
            ...$usage,
            'organization_id' => $context->organization?->id,
            'organization_name' => $context->organization?->name,
            'public_mirror' => $context->publicMirror,
            'cost_usd' => $this->costs->cost(
                $config['model'],
                $usage['input_tokens'],
                $usage['output_tokens'],
                $usage['cache_read_tokens'],
                $usage['cache_write_tokens'],
            ),
            'duration_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
        ]);
        $call->subject()->associate($context->subject);
        $call->save();

        Log::channel('ai')->info('AI call', [
            'ai_call_id' => $call->id,
            'task' => $call->task,
            'model' => $call->model_version ?? $call->model,
            'status' => $call->status->value,
            'tokens' => $call->totalTokens(),
            'duration_ms' => $call->duration_ms,
        ]);

        return $call;
    }
}
