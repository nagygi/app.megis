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
use Laravel\Ai\Exceptions\AiException;
use Laravel\Ai\Files\Audio;
use Laravel\Ai\Files\LocalAudio;
use Laravel\Ai\Files\StoredAudio;
use Laravel\Ai\Gateway\FakeTextGateway;
use Laravel\Ai\PendingResponses\PendingTranscriptionGeneration;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\FinishReason;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Laravel\Ai\Responses\TranscriptionResponse;
use Laravel\Ai\Transcription;
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
            $this->log($task->key(), $context, $config, $prompt, $startedAt, error: $exception);

            throw $exception;
        }

        $finishReason = $response->steps->last()?->finishReason;
        $unusable = $this->unusableAnswer($response, $finishReason, $config);

        $call = $this->log($task->key(), $context, $config, $prompt, $startedAt, usage: [
            'input_tokens' => $response->usage->inputTokens,
            'output_tokens' => $response->usage->outputTokens,
            'cache_read_tokens' => $response->usage->cacheReadInputTokens ?? 0,
            'cache_write_tokens' => $response->usage->cacheWriteInputTokens ?? 0,
        ], modelVersion: $response->meta->model, error: $unusable, finishReason: $finishReason);

        if ($unusable !== null) {
            throw $unusable;
        }

        return new AiResult(
            text: $response->text,
            structured: $response instanceof StructuredAgentResponse ? $response->structured : null,
            call: $call,
        );
    }

    /**
     * Transcription with speaker recognition, logged with the audio minutes
     * (16 · 16.1, 16.4). The provider and model come from the "transcription" task.
     *
     * @return array{response: TranscriptionResponse, call: AiCall}
     */
    public function transcribe(string $disk, string $path, AiContext $context = new AiContext): array
    {
        $config = config('ai.tasks.transcription');

        if (! is_array($config) || blank($config['model'] ?? null)) {
            throw new RuntimeException('The transcription task has no model yet (12.7 PoC).');
        }

        return $this->runTranscription($this->asAudio(Audio::fromStorage($path, $disk)), $config, $context);
    }

    /**
     * A local file with any provider and model, for the 12.7 comparison; the
     * calls are logged and priced like every other.
     *
     * @return array{response: TranscriptionResponse, call: AiCall}
     */
    public function transcribeFile(string $localPath, string $provider, string $model, int $timeout = 1800): array
    {
        return $this->runTranscription($this->asAudio(Audio::fromPath($localPath)), [
            'provider' => $provider,
            'model' => $model,
            'timeout' => $timeout,
        ], new AiContext);
    }

    /**
     * A browser recording (WebM, MP4) is read as video by file detection,
     * but goes to the provider as audio.
     */
    private function asAudio(StoredAudio|LocalAudio $audio): PendingTranscriptionGeneration
    {
        $mime = $audio->mimeType();

        if ($mime !== null && str_starts_with($mime, 'video/')) {
            $audio->withMimeType('audio/'.substr($mime, strlen('video/')));
        }

        return Transcription::of($audio);
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{response: TranscriptionResponse, call: AiCall}
     */
    private function runTranscription(PendingTranscriptionGeneration $pending, array $config, AiContext $context): array
    {
        $startedAt = hrtime(true);

        try {
            $response = $pending
                ->diarize()
                ->language('hu')
                ->timeout((int) ($config['timeout'] ?? 300))
                ->generate(provider: $config['provider'], model: $config['model']);
        } catch (Throwable $exception) {
            $this->log('transcription', $context, $config, null, $startedAt, error: $exception);

            throw $exception;
        }

        $call = $this->log('transcription', $context, $config, null, $startedAt, usage: [
            'input_tokens' => $response->usage->inputTokens,
            'output_tokens' => $response->usage->outputTokens,
            'cache_read_tokens' => 0,
            'cache_write_tokens' => 0,
        ], modelVersion: $response->meta->model, audioMinutes: $response->usage->audioSeconds === null ? null : round($response->usage->audioSeconds / 60, 2));

        return ['response' => $response, 'call' => $call];
    }

    /**
     * The provider may answer "completed" with nothing usable: an answer cut
     * off at max_tokens (thinking counts into it with Gemini), or a structured
     * answer that is no valid JSON, which the SDK reads as []. Both fail the
     * call, so the job retries it instead of going on with nothing. Every
     * schema has required top-level keys, so [] is never a real answer.
     *
     * @param  array<string, mixed>  $config
     */
    private function unusableAnswer(AgentResponse $response, ?FinishReason $finishReason, array $config): ?AiException
    {
        if ($finishReason === FinishReason::Length) {
            return new AiException('The answer reached max_tokens ('.($config['max_tokens'] ?? 'default').') and was cut off.');
        }

        if ($response instanceof StructuredAgentResponse && $response->structured === []) {
            return new AiException('The structured answer could not be read.');
        }

        return null;
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
        string $task,
        AiContext $context,
        array $config,
        ?Prompt $prompt,
        int|float $startedAt,
        ?array $usage = null,
        ?string $modelVersion = null,
        ?Throwable $error = null,
        ?float $audioMinutes = null,
        ?FinishReason $finishReason = null,
    ): AiCall {
        $usage ??= ['input_tokens' => 0, 'output_tokens' => 0, 'cache_read_tokens' => 0, 'cache_write_tokens' => 0];

        $call = new AiCall([
            'task' => $task,
            'provider' => $config['provider'],
            'model' => $config['model'],
            'model_version' => $modelVersion,
            'prompt' => $prompt?->name,
            'prompt_version' => $prompt?->version,
            'status' => $error === null ? AiCallStatus::Succeeded : AiCallStatus::Failed,
            'error' => $error === null ? null : mb_substr(class_basename($error).': '.$error->getMessage(), 0, 1000),
            ...$usage,
            'audio_minutes' => $audioMinutes,
            'organization_id' => $context->organization?->id,
            'organization_name' => $context->organization?->name,
            'public_mirror' => $context->publicMirror,
            'cost_usd' => $this->costs->cost(
                $config['model'],
                $usage['input_tokens'],
                $usage['output_tokens'],
                $usage['cache_read_tokens'],
                $usage['cache_write_tokens'],
                $audioMinutes,
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
            'finish_reason' => $finishReason?->value,
            'tokens' => $call->totalTokens(),
            'duration_ms' => $call->duration_ms,
        ]);

        return $call;
    }
}
