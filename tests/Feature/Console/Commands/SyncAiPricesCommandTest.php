<?php

use App\Models\AiCall;
use App\Models\AiModelPrice;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;

const PRICE_LIST_URL = 'https://prices.test/model_prices.json';

/**
 * A slice of the LiteLLM price list: USD per token and per second.
 *
 * @return array<string, array<string, mixed>>
 */
function litellmPriceList(): array
{
    return [
        'gemini/gemini-3.8-flash' => [
            'litellm_provider' => 'gemini',
            'mode' => 'chat',
            'input_cost_per_token' => 7.5e-7,
            'output_cost_per_token' => 3.75e-6,
            'cache_read_input_token_cost' => 7.5e-8,
        ],
        'gemini-3.8-flash' => [
            'litellm_provider' => 'vertex_ai-language-models',
            'mode' => 'chat',
            'input_cost_per_token' => 9e-7,
            'output_cost_per_token' => 4e-6,
        ],
        'gemini/gemini-3.5-transcribe' => [
            'litellm_provider' => 'gemini',
            'mode' => 'audio_transcription',
            'input_cost_per_token' => 1e-6,
            'input_cost_per_audio_token' => 2e-6,
            'output_cost_per_token' => 1.2e-5,
        ],
        'whisper-1' => [
            'litellm_provider' => 'openai',
            'mode' => 'audio_transcription',
            'input_cost_per_second' => 0.0001,
            'output_cost_per_second' => 0.0001,
        ],
    ];
}

beforeEach(function () {
    config([
        'ai.price_source' => PRICE_LIST_URL,
        'ai.prices' => [],
        'ai.tasks' => [
            'mirror_detection' => ['provider' => 'gemini', 'model' => 'gemini-3.8-flash'],
            'transcription' => ['provider' => 'gemini', 'model' => 'gemini-3.5-transcribe'],
            'calendar_naming' => ['provider' => 'gemini', 'model' => null],
        ],
    ]);
});

it('stores the prices of the task models per million tokens', function () {
    Http::fake([PRICE_LIST_URL => Http::response(litellmPriceList())]);

    $this->artisan('megis:prices:sync')
        ->expectsOutputToContain('2 modell ára frissítve.')
        ->assertSuccessful();

    $flash = AiModelPrice::query()->where('model', 'gemini-3.8-flash')->sole();
    expect($flash->toPriceArray())->toBe(['input' => 0.75, 'output' => 3.75, 'cache_read' => 0.075])
        ->and($flash->source_key)->toBe('gemini/gemini-3.8-flash');
    expect(AiModelPrice::query()->where('model', 'gemini-3.5-transcribe')->sole()->toPriceArray())
        ->toBe(['input' => 2.0, 'output' => 12.0]);
});

it('prices a model by the second as audio minutes', function () {
    Http::fake([PRICE_LIST_URL => Http::response(litellmPriceList())]);

    $this->artisan('megis:prices:sync', ['--model' => ['openai:whisper-1']])->assertSuccessful();

    expect(AiModelPrice::query()->where('model', 'whisper-1')->sole()->toPriceArray())
        ->toBe(['input' => 0.0, 'output' => 0.0, 'audio_minute' => 0.006]);
});

it('keeps the earlier price of a model the list no longer has', function () {
    Http::fake([PRICE_LIST_URL => Http::response(litellmPriceList())]);
    $price = AiModelPrice::factory()->create(['model' => 'claude-sonnet-5-5', 'input' => 2.0]);

    $this->artisan('megis:prices:sync')
        ->expectsOutputToContain('Nincs ár az árlistában: claude-sonnet-5-5')
        ->assertSuccessful();

    expect($price->fresh()->input)->toBe(2.0);
});

it('fails and keeps the prices when the list cannot be fetched', function () {
    Exceptions::fake();
    Http::fake([PRICE_LIST_URL => Http::response('', 500)]);
    $price = AiModelPrice::factory()->create(['model' => 'gemini-3.8-flash', 'input' => 0.5]);

    $this->artisan('megis:prices:sync')
        ->expectsOutputToContain('Az árlista nem tölthető le')
        ->assertFailed();

    expect($price->fresh()->input)->toBe(0.5);
    Exceptions::assertReported(RequestException::class);
});

it('refuses a model without a provider', function () {
    Http::fake([PRICE_LIST_URL => Http::response(litellmPriceList())]);

    $this->artisan('megis:prices:sync', ['--model' => ['whisper-1']])
        ->expectsOutputToContain('Hibás modell: whisper-1')
        ->assertFailed();

    Http::assertNothingSent();
});

it('prices the calls logged without a price, and only those', function () {
    Http::fake([PRICE_LIST_URL => Http::response(litellmPriceList())]);
    $unpriced = AiCall::factory()->create(['model' => 'gemini-3.8-flash', 'input_tokens' => 1_000_000, 'output_tokens' => 100_000, 'cost_usd' => null]);
    $priced = AiCall::factory()->create(['model' => 'gemini-3.8-flash', 'cost_usd' => 0.5]);
    $unknown = AiCall::factory()->create(['model' => 'gemini-9-pro', 'cost_usd' => null]);

    $this->artisan('megis:prices:sync', ['--backfill' => true])
        ->expectsOutputToContain('1 korábbi hívás utólag beárazva.')
        ->assertSuccessful();

    expect($unpriced->fresh()->cost_usd)->toBe(1.125)
        ->and($priced->fresh()->cost_usd)->toBe(0.5)
        ->and($unknown->fresh()->cost_usd)->toBeNull();
});
