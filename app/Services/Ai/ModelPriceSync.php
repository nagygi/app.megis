<?php

namespace App\Services\Ai;

use App\Models\AiCall;
use App\Models\AiModelPrice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Refreshes the unit prices from the LiteLLM price list (16 · 16.4). The list
 * gives USD per token and per second; the app keeps USD per million tokens
 * and per minute of audio.
 */
class ModelPriceSync
{
    public function __construct(private CostCalculator $costs) {}

    /**
     * The models of the AI tasks and the ones priced before.
     *
     * @return array<string, string> provider by model
     */
    public function knownModels(): array
    {
        $models = AiModelPrice::query()->pluck('provider', 'model')->all();

        foreach (config('ai.tasks') as $task) {
            if (filled($task['model'] ?? null)) {
                $models[$task['model']] = $task['provider'];
            }
        }

        return $models;
    }

    /**
     * A model missing from the list keeps its earlier price. Nothing changes
     * when the list cannot be fetched.
     *
     * @param  array<string, string>  $models  provider by model
     * @return array{synced: list<AiModelPrice>, missing: list<string>}
     */
    public function sync(array $models): array
    {
        $list = Http::timeout(30)->get(config('ai.price_source'))->throw()->json();

        if (! is_array($list)) {
            throw new RuntimeException('The price list is not a JSON object.');
        }

        $synced = [];
        $missing = [];

        foreach ($models as $model => $provider) {
            $found = $this->entryFor($list, $provider, $model);

            if ($found === null) {
                $missing[] = $model;

                continue;
            }

            [$key, $entry] = $found;

            $synced[] = AiModelPrice::query()->updateOrCreate(['model' => $model], [
                'provider' => $provider,
                ...$this->pricesOf($entry),
                'source_key' => $key,
                'synced_at' => now(),
            ]);
        }

        return ['synced' => $synced, 'missing' => $missing];
    }

    /**
     * Prices the calls logged without a price whose model has one now. A call
     * with a cost keeps it: a new price never changes an old call (16 · 16.4).
     */
    public function backfill(): int
    {
        $priced = AiModelPrice::query()->pluck('model')
            ->merge(array_keys(config('ai.prices') ?? []))
            ->unique()
            ->all();
        $count = 0;

        AiCall::query()
            ->whereNull('cost_usd')
            ->whereIn('model', $priced)
            ->chunkById(200, function (Collection $calls) use (&$count): void {
                foreach ($calls as $call) {
                    $call->update(['cost_usd' => $this->costs->cost(
                        $call->model,
                        $call->input_tokens,
                        $call->output_tokens,
                        $call->cache_read_tokens,
                        $call->cache_write_tokens,
                        $call->audio_minutes,
                    )]);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * The provider's own entry ("gemini/gemini-3.8-flash") first, then the
     * bare model name, which LiteLLM uses for OpenAI and Anthropic.
     *
     * @param  array<string, mixed>  $list
     * @return array{0: string, 1: array<string, mixed>}|null
     */
    private function entryFor(array $list, string $provider, string $model): ?array
    {
        foreach ([$provider.'/'.$model, $model] as $key) {
            $entry = $list[$key] ?? null;

            if (is_array($entry) && (is_numeric($entry['input_cost_per_token'] ?? null) || is_numeric($entry['input_cost_per_second'] ?? null))) {
                return [$key, $entry];
            }
        }

        return null;
    }

    /**
     * A transcription model's input is audio, so its audio token price counts.
     * A per-second price is used only without token prices (whisper-1), so a
     * call is never charged twice.
     *
     * @param  array<string, mixed>  $entry
     * @return array{input: float, output: float, cache_read: ?float, cache_write: ?float, audio_minute: ?float}
     */
    private function pricesOf(array $entry): array
    {
        $perMillion = fn (string $field): ?float => is_numeric($entry[$field] ?? null)
            ? round((float) $entry[$field] * 1_000_000, 6)
            : null;

        $input = ($entry['mode'] ?? null) === 'audio_transcription'
            ? $perMillion('input_cost_per_audio_token') ?? $perMillion('input_cost_per_token')
            : $perMillion('input_cost_per_token');

        return [
            'input' => $input ?? 0.0,
            'output' => $perMillion('output_cost_per_token') ?? 0.0,
            'cache_read' => $perMillion('cache_read_input_token_cost'),
            'cache_write' => $perMillion('cache_creation_input_token_cost'),
            'audio_minute' => $input === null && is_numeric($entry['input_cost_per_second'] ?? null)
                ? round((float) $entry['input_cost_per_second'] * 60, 6)
                : null,
        ];
    }
}
