<?php

use App\Enums\DiagnosisCreator;
use App\Enums\DiagnosisStatus;
use App\Enums\MaterialStatus;
use App\Enums\ValueOrigin;
use App\Livewire\Admin\Diagnoses\Show as DiagnosisShow;
use App\Livewire\Admin\Diagnoses\Upload;
use App\Livewire\Admin\Materials\Show;
use App\Livewire\Admin\Values\Form;
use App\Models\DiagnosisType;
use App\Models\Material;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Models\Value;
use App\Services\Ai\AiGateway;
use App\Services\Values\ValueService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\StructuredTextResponse;
use Livewire\Livewire;

function uploadAnswer(array $data): StructuredTextResponse
{
    return new StructuredTextResponse($data, json_encode($data), new TextUsage(10, 5), new Meta('anthropic', 'claude-test'));
}

it('turns a transcript into one diagnosis per assigned speaker', function () {
    actingAsAdmin();
    $organization = Organization::factory()->create();
    $anna = User::factory()->create(['first_name' => 'Anna', 'last_name' => 'Kiss']);
    Membership::factory()->for($organization)->for($anna)->create();
    $value = app(ValueService::class)->create(['name' => 'Felelősség', 'meanings' => ['Állom.'], 'high_examples' => ['Mindig.'], 'low_examples' => ['Soha.']]);

    Livewire::test(Upload::class)
        ->set('organization_id', $organization->id)
        ->set('diagnosis_type_id', DiagnosisType::byKey('workshop')->id)
        ->set('diagnosed_on', '2026-10-01')
        ->set('file', UploadedFile::fake()->createWithContent('vezetoi-kor.txt', "Anna: Ha én írtam alá, én állom.\nPéter: Szóljunk most az ügyfélnek.\nAnna: Rendben."))
        ->call('save')
        ->assertHasNoErrors();

    $material = Material::query()->sole();
    expect($material->status)->toBe(MaterialStatus::AwaitingSpeakers)
        ->and($material->speakers())->toBe(['Anna', 'Péter']);

    AiGateway::fake([
        uploadAnswer(['detections' => [
            ['speaker' => 'Anna', 'quote' => 'Ha én írtam alá, én állom.', 'free_name' => 'Felelősség', 'reasoning' => 'Vállalja.', 'strength' => 6],
            ['speaker' => 'Péter', 'quote' => 'Szóljunk most az ügyfélnek.', 'free_name' => 'Nyíltság', 'reasoning' => 'Azonnal szól.', 'strength' => 4],
        ]]),
        uploadAnswer(['results' => [['detection' => 'D1', 'matches' => [['ref' => 'E1', 'confidence' => 0.95]]], ['detection' => 'D2', 'matches' => []]]]),
        uploadAnswer(['scores' => [['ref' => 'E1', 'state' => 'scored', 'score' => 6, 'reasoning' => 'Beépült.', 'detections' => ['D1']]]]),
    ]);

    Livewire::test(Show::class, ['material' => $material])
        ->assertSee('Ha én írtam alá, én állom.')
        ->call('assign')
        ->assertHasErrors(['assignments'])
        ->set('assignments.0', (string) $anna->id)
        ->call('assign')
        ->assertHasNoErrors();

    $diagnosis = $material->fresh()->diagnoses()->sole();
    expect($diagnosis)
        ->user_id->toBe($anna->id)
        ->speaker->toBe('Anna')
        ->name->toBe('vezetoi-kor.txt')
        ->creator_kind->toBe(DiagnosisCreator::Automatic)
        ->status->toBe(DiagnosisStatus::PendingApproval)
        ->and($diagnosis->diagnosed_on->toDateString())->toBe('2026-10-01')
        ->and($diagnosis->valueRows()->sole()->value_id)->toBe($value->id);

    // The diagnosis page shows the quote, the transcript and the source.
    Livewire::test(DiagnosisShow::class, ['diagnosis' => $diagnosis])
        ->assertSee('„Ha én írtam alá, én állom.”', false)
        ->assertSee('Átirat');
});

it('lists the upload rules and refuses an unknown file type', function () {
    actingAsAdmin();

    Livewire::test(Upload::class)
        ->assertSee('Hang: mp3, m4a, wav, webm, legfeljebb 90 perc és 300 MB.')
        ->set('organization_id', Organization::factory()->create()->id)
        ->set('file', UploadedFile::fake()->create('kep.png', 10, 'image/png'))
        ->call('save')
        ->assertHasErrors(['file' => 'extensions']);
});

it('lets the admin delete the recording and keeps the transcript', function () {
    Storage::fake('materials');
    actingAsAdmin();
    Storage::disk('materials')->put('abc/audio.webm', 'hang');
    $material = Material::factory()->recording('abc/audio.webm')->transcribed([['speaker' => 'Beszélő 1', 'text' => 'Átirat marad.']])->create(['status' => MaterialStatus::Processed]);

    Livewire::test(Show::class, ['material' => $material])->call('deleteAudio');

    $material->refresh();
    expect($material->hasAudio())->toBeFalse()
        ->and($material->text)->toBe('Átirat marad.');
    Storage::disk('materials')->assertMissing('abc/audio.webm');
});

it('plays the audio only for admins', function () {
    Storage::fake('materials');
    Storage::disk('materials')->put('abc/audio.webm', 'hang');
    $material = Material::factory()->recording('abc/audio.webm')->create();

    actingAsAdmin();
    $this->get(route('admin.materials.audio', $material))->assertOk()->assertHeader('Cache-Control', 'no-store, private');

    auth('admin')->logout();
    actingAsCustomer(customerOf());
    $this->get(route('admin.materials.audio', $material))->assertForbidden();
});

it('shows where an AI-detected value came up', function () {
    actingAsAdmin();
    $value = Value::factory()->from(ValueOrigin::Ai)->create();
    $material = Material::factory()->create();
    $material->detections()->create(['quote' => 'A végén nevettünk egyet.', 'free_name' => $value->name, 'unmatched_value_id' => $value->id]);

    Livewire::test(Form::class, ['value' => $value])
        ->assertSee('Előfordulások')
        ->assertSee('A végén nevettünk egyet.');
});
