<?php

namespace App\Console\Commands;

use App\Models\AiModelPrice;
use App\Services\Ai\ModelPriceSync;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

/**
 * The daily price refresh of the models in use, from the LiteLLM price list
 * (16 · 16.4). With --backfill it also prices the calls logged without a price.
 */
#[Signature('megis:prices:sync {--model=* : szolgáltató:modell, pl. openai:whisper-1} {--backfill : Az ár nélkül naplózott hívások utólagos beárazása}')]
#[Description('AI-modellek egységárainak frissítése a LiteLLM árlistából (16 · 16.4)')]
class SyncAiPricesCommand extends Command
{
    public function handle(ModelPriceSync $prices): int
    {
        $models = $prices->knownModels();

        foreach ((array) $this->option('model') as $option) {
            [$provider, $model] = array_pad(explode(':', (string) $option, 2), 2, '');

            if ($provider === '' || $model === '') {
                $this->error(__('console.prices_sync.invalid_model', ['option' => $option]));

                return self::FAILURE;
            }

            $models[$model] = $provider;
        }

        try {
            ['synced' => $synced, 'missing' => $missing] = $prices->sync($models);
        } catch (Throwable $exception) {
            report($exception);
            $this->error(__('console.prices_sync.failed', ['message' => $exception->getMessage()]));

            return self::FAILURE;
        }

        $this->table(__('console.prices_sync.columns'), array_map(fn (AiModelPrice $price) => [
            $price->model,
            $price->source_key,
            $this->usd($price->input),
            $this->usd($price->output),
            $this->usd($price->cache_read),
            $this->usd($price->audio_minute),
        ], $synced));

        foreach ($missing as $model) {
            $this->warn(__('console.prices_sync.missing', ['model' => $model]));
        }

        $this->info(__('console.prices_sync.done', ['count' => count($synced)]));

        if ($this->option('backfill')) {
            $this->info(__('console.prices_sync.backfilled', ['count' => $prices->backfill()]));
        }

        return self::SUCCESS;
    }

    private function usd(?float $price): string
    {
        return $price === null ? '–' : '$'.number_format($price, 4, ',', '');
    }
}
