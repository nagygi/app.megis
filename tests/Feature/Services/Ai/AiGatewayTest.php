<?php

use App\Ai\AiContext;
use App\Enums\AiCallStatus;
use App\Enums\InboxItemType;
use App\Models\AiCall;
use App\Models\InboxItem;
use App\Models\Material;
use App\Models\Organization;
use App\Models\Value;
use App\Services\Ai\AiGateway;
use App\Services\Ai\PromptRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Exceptions\AiException;
use Laravel\Ai\Prompts\TranscriptionPrompt;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\Data\TranscriptionUsage;
use Laravel\Ai\Responses\StructuredTextResponse;
use Laravel\Ai\Responses\TextResponse;
use Laravel\Ai\Responses\TranscriptionResponse;
use Laravel\Ai\Transcription;
use Tests\Fixtures\Ai\TestAiJob;
use Tests\Fixtures\Ai\TestTask;

beforeEach(function () {
    config([
        'ai.prompts_path' => base_path('tests/Fixtures/prompts'),
        'ai.tasks.test_task' => [
            'provider' => 'anthropic',
            'model' => 'claude-test',
            'max_tokens' => 1000,
            'temperature' => 0.0,
            'timeout' => 30,
            'prompt' => 'teszt-feladat',
            'prompt_version' => 2,
        ],
        'ai.prices.claude-test' => ['input' => 3.0, 'output' => 15.0, 'cache_read' => 0.3],
    ]);
});

it('logs the exact model, the prompt version, the tokens and the cost of a call', function () {
    AiGateway::fake([
        new TextResponse('kész', new TextUsage(1000, 200, cacheReadInputTokens: 400), new Meta('anthropic', 'claude-test-20260901')),
    ]);
    $organization = Organization::factory()->create(['name' => 'Acme Kft.']);
    $value = Value::factory()->create();

    $result = app(AiGateway::class)->run(new TestTask, AiContext::forOrganization($organization, $value));

    expect($result->text)->toBe('kész')
        ->and($result->call)
        ->task->toBe('test_task')
        ->provider->toBe('anthropic')
        ->model->toBe('claude-test')
        ->model_version->toBe('claude-test-20260901')
        ->prompt->toBe('teszt-feladat')
        ->prompt_version->toBe(2)
        ->status->toBe(AiCallStatus::Succeeded)
        ->input_tokens->toBe(1000)
        ->output_tokens->toBe(200)
        ->cache_read_tokens->toBe(400)
        ->organization_name->toBe('Acme Kft.')
        // 600 × $3 + 400 × $0,3 + 200 × $15, per million tokens
        ->cost_usd->toBe(0.00492)
        ->and($result->call->subject->is($value))->toBeTrue();
});

it('returns the structured answer of a task with a schema', function () {
    AiGateway::fake([
        new StructuredTextResponse(['values' => ['Bátorság']], '{}', new TextUsage(10, 5), new Meta('anthropic', 'claude-test')),
    ]);

    $result = app(AiGateway::class)->run(new TestTask(structured: true));

    expect($result->structured)->toBe(['values' => ['Bátorság']]);
});

it('logs a failed call and lets the job retry it', function () {
    AiGateway::fake(fn () => throw new RuntimeException('Provider timeout'));

    expect(fn () => app(AiGateway::class)->run(new TestTask, AiContext::publicMirror()))
        ->toThrow(RuntimeException::class, 'Provider timeout');

    expect(AiCall::query()->sole())
        ->status->toBe(AiCallStatus::Failed)
        ->error->toBe('RuntimeException: Provider timeout')
        ->public_mirror->toBeTrue()
        ->and(AiCall::query()->sole()->contextLabel())->toBe('Nyilvános Értéktükör');
});

it('fails a call whose answer was cut off at max_tokens, though the provider completed it', function () {
    // Gemini counts its thinking into max_tokens; the cut JSON reads as [] (Értéktükör teszt 1).
    config([
        'ai.tasks.test_task.provider' => 'gemini',
        'ai.tasks.test_task.model' => 'gemini-test',
        'ai.providers.gemini.key' => 'test-key',
    ]);
    Http::fake(['*interactions*' => Http::response([
        'status' => 'incomplete',
        'steps' => [['type' => 'model_output', 'content' => [['type' => 'text', 'text' => '{"values": ["Bátor']]]],
        'usage' => ['total_input_tokens' => 900, 'total_output_tokens' => 15, 'total_thought_tokens' => 970],
    ])]);

    expect(fn () => app(AiGateway::class)->run(new TestTask(structured: true)))
        ->toThrow(AiException::class, 'The answer reached max_tokens (1000) and was cut off.');

    expect(AiCall::query()->sole())
        ->status->toBe(AiCallStatus::Failed)
        ->error->toBe('AiException: The answer reached max_tokens (1000) and was cut off.')
        ->input_tokens->toBe(900)
        ->output_tokens->toBe(985);
});

it('fails a structured call whose answer cannot be read', function () {
    AiGateway::fake([
        new StructuredTextResponse([], 'Sajnos nem tudok segíteni.', new TextUsage(10, 5), new Meta('anthropic', 'claude-test')),
    ]);

    expect(fn () => app(AiGateway::class)->run(new TestTask(structured: true)))
        ->toThrow(AiException::class, 'The structured answer could not be read.');

    expect(AiCall::query()->sole()->status)->toBe(AiCallStatus::Failed);
});

it('saves no cost for a model without a price', function () {
    config(['ai.prices' => []]);
    AiGateway::fake(['kész']);

    $result = app(AiGateway::class)->run(new TestTask);

    expect($result->call->cost_usd)->toBeNull();
});

it('does not run a task without an active prompt', function () {
    config(['ai.tasks.test_task.prompt_version' => null]);
    AiGateway::fake(['kész']);

    expect(fn () => app(AiGateway::class)->run(new TestTask))->toThrow(RuntimeException::class);
    expect(AiCall::query()->count())->toBe(0);
});

it('lists prompt versions and fills the placeholders it knows', function () {
    $prompts = app(PromptRepository::class);

    expect($prompts->versions('teszt-feladat'))->toBe([1, 2])
        ->and($prompts->activeFor('test_task')->render(['organization' => 'Acme Kft.']))
        ->toBe("Te a(z) Acme Kft. értékeit keresed a szövegben. Ismeretlen: {{ unknown }}.\n");
});

it('opens one processing error row when a job fails for good', function () {
    $organization = Organization::factory()->create();
    $value = Value::factory()->create();
    $job = new TestAiJob($organization, $value);

    $job->failed(new RuntimeException('x'));
    $job->failed(new RuntimeException('x'));

    expect($job->markedFailed)->toBeTrue()
        ->and($job->queue)->toBe('ai')
        ->and($job->tries)->toBe(3)
        ->and(InboxItem::query()->sole())
        ->type->toBe(InboxItemType::ProcessingError)
        ->organization_id->toBe($organization->id)
        ->summary->toBe('Nem tudtuk feldolgozni a teszt anyagot.');
});

it('sends a browser recording to the transcriber as audio, not video', function () {
    Storage::fake(Material::DISK);
    // The start of a WebM file, which file detection reads as video/webm.
    Storage::disk(Material::DISK)->put('felvetel/audio.webm', "\x1A\x45\xDF\xA3\x9F\x42\x86\x81\x01\x42\xF7\x81\x01\x42\xF2\x81\x04\x42\xF3\x81\x08\x42\x82\x84webm\x42\x87\x81\x04\x42\x85\x81\x02".str_repeat("\0", 64));
    config(['ai.tasks.transcription.model' => 'gemini-test']);
    Transcription::fake([
        new TranscriptionResponse('Szia.', collect(), new TranscriptionUsage(audioSeconds: 2), new Meta('gemini', 'gemini-test')),
    ]);

    app(AiGateway::class)->transcribe(Material::DISK, 'felvetel/audio.webm');

    Transcription::assertGenerated(fn (TranscriptionPrompt $prompt): bool => $prompt->audio->mimeType() === 'audio/webm');
});
