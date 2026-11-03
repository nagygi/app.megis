<?php

use App\Ai\AiContext;
use App\Ai\Tasks\DetectValuesTask;
use App\Ai\Tasks\ScoreValuesTask;
use App\Enums\MaterialContext;
use App\Enums\MaterialStatus;
use App\Enums\ScoreResultState;
use App\Enums\ValueOrigin;
use App\Models\Material;
use App\Models\Organization;
use App\Models\Process;
use App\Models\ProcessOccasion;
use App\Models\Value;
use App\Services\Ai\AiGateway;
use App\Services\Ai\PromptRepository;
use App\Services\Engine\ValueCatalog;
use App\Services\Engine\ValueMeasurementEngine;
use App\Services\Materials\MaterialPipeline;
use App\Services\Values\ValueService;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\StructuredTextResponse;

/**
 * A processed material of an occasion of "Heti kör", a subprocess of "Vezetés".
 */
function occasionMaterial(?Organization $organization = null): Material
{
    $organization ??= Organization::factory()->create();
    $parent = Process::factory()->accepted()->for($organization)->create(['name' => 'Vezetés']);
    $process = Process::factory()->accepted()->for($organization)->create(['name' => 'Heti kör', 'rhythm_label' => 'keddenként 9:00', 'parent_id' => $parent->id]);
    $occasion = ProcessOccasion::create(['process_id' => $process->id, 'occurred_at' => now()->subDay(), 'source' => 'manual']);

    return Material::factory()->create([
        'context' => MaterialContext::Occasion,
        'status' => MaterialStatus::Processed,
        'organization_id' => $organization->id,
        'process_occasion_id' => $occasion->id,
    ]);
}

it('tells detection and scoring which process the occasion belongs to, not a mirror (13 · 13.2)', function () {
    $engine = app(ValueMeasurementEngine::class);
    $situation = $engine->situation(occasionMaterial());
    $prompts = app(PromptRepository::class);

    expect($situation)->toContain('„Heti kör”')
        ->toContain('ritmusa: keddenként 9:00')
        ->toContain('a(z) „Vezetés” folyamat része')
        ->and($prompts->activeFor('mirror_detection')->render((new DetectValuesTask('szöveg', $situation))->variables()))->toContain('# A szöveg helyzete')
        ->and($prompts->activeFor('mirror_scoring')->render((new ScoreValuesTask('[E1]', [], $situation))->variables()))->toContain('„Heti kör”');

    $mirror = Material::factory()->create(['context' => MaterialContext::Mirror]);
    $rendered = $prompts->activeFor('mirror_detection')->render((new DetectValuesTask('szöveg', $engine->situation($mirror)))->variables());

    expect($engine->situation($mirror))->toBe('')
        ->and($rendered)->not->toContain('{{ context }}')
        ->and($rendered)->not->toContain('A szöveg helyzete');
});

it('makes what matched nothing in an occasion material an AI-detected value (06 · 6.4, 13 · 13.2)', function () {
    Queue::fake();
    $material = occasionMaterial();
    $value = app(ValueService::class)->create(['name' => 'Egyenesség', 'meanings' => ['Kimondom.']]);
    $matched = $material->detections()->create(['speaker' => 'Beszélő 1', 'quote' => 'inkább most mondom', 'free_name' => 'Egyenes beszéd', 'reasoning' => 'R']);
    $matched->matches()->create(['value_id' => $value->id, 'confidence' => 0.9]);
    $unmatched = $material->detections()->create(['speaker' => 'Beszélő 1', 'quote' => 'a végén nevettünk egyet', 'free_name' => 'Játékosság', 'reasoning' => 'Humor marad.']);

    app(MaterialPipeline::class)->analyzed($material);

    expect($unmatched->refresh()->unmatchedValue)->not->toBeNull()
        ->and($unmatched->unmatchedValue->origin)->toBe(ValueOrigin::Ai)
        ->and($unmatched->unmatchedValue->name)->toBe('Játékosság')
        ->and($matched->refresh()->unmatched_value_id)->toBeNull();
});

it('keeps one score per value when the answer names a value twice', function () {
    $material = occasionMaterial();
    $value = app(ValueService::class)->create(['name' => 'Egyenesség', 'meanings' => ['Kimondom.']]);
    $detection = $material->detections()->create(['speaker' => 'Beszélő 1', 'quote' => 'inkább most mondom', 'free_name' => 'Egyenes beszéd', 'reasoning' => 'R']);
    $detection->matches()->create(['value_id' => $value->id, 'confidence' => 0.9]);
    $catalog = ValueCatalog::forOrganization($material->organization);
    $ref = (string) $catalog->refFor($value->id);
    $answer = ['scores' => [
        ['ref' => $ref, 'state' => 'scored', 'score' => 5, 'reasoning' => 'Visszatérően.', 'detections' => ['D1']],
        ['ref' => $ref, 'state' => 'scored', 'score' => 2, 'reasoning' => 'Ritkán.', 'detections' => ['D1']],
    ]];
    AiGateway::fake([new StructuredTextResponse($answer, json_encode($answer), new TextUsage(10, 5), new Meta('anthropic', 'claude-test'))]);

    app(ValueMeasurementEngine::class)->score($material, null, $catalog, new AiContext);

    expect($material->scores()->where('value_id', $value->id)->get())->toHaveCount(1)
        ->and($material->scores()->where('value_id', $value->id)->sole())->state->toBe(ScoreResultState::Scored)->score->toEqual(5);
});

it('never shows a value its own name in the organization\'s words (13 · 13.2)', function () {
    $organization = Organization::factory()->create();
    $value = app(ValueService::class)->create(['name' => 'Egyenesség', 'meanings' => ['Kimondom.']]);
    $catalog = ValueCatalog::forDiagnosis($organization, ['values' => [[
        'key' => 'egyenesseg', 'value_id' => $value->id, 'name' => 'Egyenesség',
        'meaning' => 'Az egyenesség nálunk azt jelenti, hogy kimondjuk.', 'non_meaning' => null,
        'low_example' => 'Az Egyenességgel szemben hallgatunk.', 'high_example' => 'Azonnal szólunk.', 'example' => null,
    ]]]);

    $prompt = $catalog->forPrompt();

    expect($prompt)->not->toContain('gyenesség')
        ->toContain('Az ez az érték nálunk azt jelenti, hogy kimondjuk.')
        ->toContain('Az ez az érték szemben hallgatunk.')
        ->and(Value::query()->whereKey($value->id)->value('name'))->toBe('Egyenesség');
});
