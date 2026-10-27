<?php

use App\Enums\InboxItemType;
use App\Enums\MainDiagnosisStage;
use App\Enums\MaterialContext;
use App\Enums\MaterialStatus;
use App\Enums\ParticipantRole;
use App\Enums\ProcessKind;
use App\Jobs\AnalyzeMaterial;
use App\Jobs\RecalculateMainDiagnosis;
use App\Livewire\Admin\Organizations\Processes;
use App\Livewire\Admin\Organizations\ProcessOccasions;
use App\Livewire\Client\Diagnosis\Contribute;
use App\Livewire\Client\Diagnosis\OccasionExperienceForm;
use App\Livewire\Client\Diagnosis\PlaceMaterial;
use App\Livewire\Client\Diagnosis\Situation;
use App\Livewire\Client\Diagnosis\Situations;
use App\Models\Consent;
use App\Models\InboxItem;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\OccasionExperience;
use App\Models\Process;
use App\Models\ProcessOccasion;
use App\Services\MainDiagnoses\ExperienceService;
use App\Services\MainDiagnoses\StageMachine;
use App\Services\Processes\OccasionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('materials');
    Queue::fake();
});

/**
 * A running team diagnosis with two measured processes; the first peer has
 * started and takes part in the first process only.
 *
 * @return array{0: array<string, mixed>, 1: MainDiagnosis, 2: Process, 3: Process}
 */
function occasionDiagnosis(): array
{
    [$setup, $main] = runningDiagnosis();
    $retro = Process::factory()->accepted()->for($setup['organization'])->create(['name' => 'Retró']);
    $planning = Process::factory()->accepted()->for($setup['organization'])->create(['name' => 'Tervezés']);
    $main->processes()->sync([$retro->id, $planning->id]);

    $peer = $setup['peers'][0];
    $main->participantFor($peer, ParticipantRole::Peer)->update(['started_at' => now()]);
    Consent::create(['user_id' => $peer->id, 'main_diagnosis_id' => $main->id, 'kind' => 'answers', 'text_version' => 'v', 'text' => 'szöveg', 'given_at' => now()]);
    $retro->participants()->create(['user_id' => $peer->id, 'source' => 'manual']);

    return [$setup, $main->refresh(), $retro, $planning];
}

function occasionParams(array $setup, MainDiagnosis $main): array
{
    return ['organization' => $setup['organization'], 'diagnosis' => $main->diagnosis];
}

function situationMaterial(MainDiagnosis $main, $user, string $text = 'A retrón végre mindenki elmondta, mi akadt el, és közösen döntöttünk.'): Material
{
    return app(OccasionService::class)->situationText($main, $user, $text);
}

it('takes an upload and asks where it belongs before processing it (V11)', function () {
    [$setup, $main] = occasionDiagnosis();
    actingAsCustomer($setup['leader']);

    $component = Livewire::test(Contribute::class, occasionParams($setup, $main))
        ->assertSee('Mérés indítása')
        ->set('file', UploadedFile::fake()->createWithContent('retro.txt', str_repeat('Megbeszéltük a hetet, és mindenki elmondta a véleményét. ', 5)))
        ->call('storeUpload')
        ->assertHasNoErrors();

    $material = Material::query()->sole();
    $component->assertRedirect(route('client.diagnosis.place', [$setup['organization'], $main->diagnosis, $material]));

    expect($material->context)->toBe(MaterialContext::Occasion)
        ->and($material->parent_diagnosis_id)->toBe($main->diagnosis_id)
        ->and($material->user_id)->toBe($setup['leader']->id)
        ->and($material->placed_at)->toBeNull();
    Queue::assertNotPushed(AnalyzeMaterial::class);
});

it('lists only the unfinished materials under "Feldolgozás alatt" (V11)', function () {
    [$setup, $main, $retro] = occasionDiagnosis();
    $leader = $setup['leader'];
    actingAsCustomer($leader);
    situationMaterial($main, $leader);
    $finished = situationMaterial($main, $leader, 'A tervezésen mindenki szólt.');
    app(OccasionService::class)->place($finished, $retro, today()->subDay()->toDateString(), $leader, false);
    $finished->refresh()->update(['status' => MaterialStatus::Processed]);
    app(OccasionService::class)->experience($finished, $leader, null, null, null, skip: true);

    Livewire::test(Contribute::class, occasionParams($setup, $main))
        ->assertSee('Feldolgozás alatt · 1')
        ->assertSee('nincs besorolva')
        ->assertSee('A helyzeteid');
});

it('rejects an unsupported file type', function () {
    [$setup, $main] = occasionDiagnosis();
    actingAsCustomer($setup['leader']);

    Livewire::test(Contribute::class, occasionParams($setup, $main))
        ->set('file', UploadedFile::fake()->create('slides.pptx', 10))
        ->call('storeUpload')
        ->assertHasErrors(['file']);

    expect(Material::query()->count())->toBe(0);
});

it('places an upload on the process occasion of that day, with the recording consent', function () {
    [$setup, $main, $retro] = occasionDiagnosis();
    $leader = $setup['leader'];
    actingAsCustomer($leader);
    $material = app(OccasionService::class)->upload($main, $leader, UploadedFile::fake()->createWithContent('retro.txt', str_repeat('Szöveg a retróról. ', 10)));
    $existing = ProcessOccasion::create(['process_id' => $retro->id, 'occurred_at' => today()->subDay()->setTime(9, 0), 'source' => 'calendar']);

    Livewire::test(PlaceMaterial::class, [...occasionParams($setup, $main), 'material' => $material])
        ->assertSee('Retró')
        ->assertSee('Tervezés')
        ->set('choice', (string) $retro->id)
        ->set('date', today()->subDay()->toDateString())
        ->call('place')
        ->assertHasErrors(['consent'])
        ->set('consent', true)
        ->call('place')
        ->assertHasNoErrors()
        ->assertRedirect(route('client.diagnosis.occasion-experience', [$setup['organization'], $main->diagnosis, $material]));

    $material->refresh();
    expect($material->process_occasion_id)->toBe($existing->id)
        ->and($material->placed_at)->not->toBeNull()
        ->and($material->recording_consent_by)->toBe($leader->id)
        ->and(ProcessOccasion::query()->count())->toBe(1);
    Queue::assertPushed(AnalyzeMaterial::class);
});

it('preselects the latest calendar occasion with its date', function () {
    [$setup, $main, $retro, $planning] = occasionDiagnosis();
    actingAsCustomer($setup['leader']);
    ProcessOccasion::create(['process_id' => $retro->id, 'occurred_at' => now()->subDays(5), 'source' => 'calendar']);
    ProcessOccasion::create(['process_id' => $planning->id, 'occurred_at' => now()->subDays(2), 'source' => 'calendar']);
    $material = situationMaterial($main, $setup['leader']);

    Livewire::test(PlaceMaterial::class, [...occasionParams($setup, $main), 'material' => $material])
        ->assertSet('choice', (string) $planning->id)
        ->assertSet('date', now()->subDays(2)->toDateString())
        ->assertSee('a naptárból');
});

it('needs no consent for a situation and creates a new occasion', function () {
    [$setup, $main, $retro] = occasionDiagnosis();
    actingAsCustomer($setup['leader']);
    $material = situationMaterial($main, $setup['leader']);

    Livewire::test(PlaceMaterial::class, [...occasionParams($setup, $main), 'material' => $material])
        ->assertDontSee('hozzájárultak')
        ->set('choice', (string) $retro->id)
        ->call('place')
        ->assertHasNoErrors();

    $occasion = ProcessOccasion::query()->sole();
    expect($occasion->process_id)->toBe($retro->id)
        ->and($occasion->source)->toBe('manual')
        ->and($occasion->created_by)->toBe($setup['leader']->id)
        ->and($material->refresh()->recording_consent_at)->toBeNull();
});

it('lets a peer choose only the processes they take part in, and shows excluded ones dashed', function () {
    [$setup, $main, $retro, $planning] = occasionDiagnosis();
    Process::factory()->for($setup['organization'])->create(['name' => 'Kávészünet', 'kind' => ProcessKind::Excluded]);
    $peer = $setup['peers'][0];
    actingAsCustomer($peer);
    $material = situationMaterial($main, $peer);

    Livewire::test(PlaceMaterial::class, [...occasionParams($setup, $main), 'material' => $material])
        ->assertSee('Retró')
        ->assertDontSee('Tervezés')
        ->assertSee('Kávészünet · nem mérjük')
        ->set('choice', (string) $planning->id)
        ->call('place')
        ->assertHasErrors(['choice']);
});

it('sends "Egyikhez sem" to the admin, who classifies it', function () {
    [$setup, $main, $retro] = occasionDiagnosis();
    actingAsCustomer($setup['leader']);
    $material = situationMaterial($main, $setup['leader']);

    Livewire::test(PlaceMaterial::class, [...occasionParams($setup, $main), 'material' => $material])
        ->set('choice', 'none')
        ->call('place')
        ->assertRedirect(route('client.diagnosis.contribute', [$setup['organization'], $main->diagnosis]));

    $item = InboxItem::query()->sole();
    expect($item->type)->toBe(InboxItemType::Classification)
        ->and($material->refresh()->process_occasion_id)->toBeNull();

    actingAsAdmin($setup['admin']);
    Livewire::test(Processes::class, ['organization' => $setup['organization']])
        ->assertSee('Besorolásra vár')
        ->set("classify.{$material->uuid}.process_id", (string) $retro->id)
        ->set("classify.{$material->uuid}.date", today()->toDateString())
        ->call('classifyMaterial', $material->uuid)
        ->assertHasNoErrors()
        ->assertDontSee('Besorolásra vár');

    expect($material->refresh()->occasion->process_id)->toBe($retro->id)
        ->and($item->refresh()->closed_at)->not->toBeNull();
    Queue::assertPushed(RecalculateMainDiagnosis::class);
});

it('marks an unclassified material as Egyéb', function () {
    [$setup, $main] = occasionDiagnosis();
    $material = situationMaterial($main, $setup['leader']);
    app(OccasionService::class)->place($material, null, today()->toDateString(), $setup['leader'], false);

    actingAsAdmin($setup['admin']);
    Livewire::test(Processes::class, ['organization' => $setup['organization']])
        ->call('markOther', $material->uuid);

    expect($material->refresh()->classified_other_at)->not->toBeNull()
        ->and(InboxItem::query()->sole()->closed_at)->not->toBeNull();
});

it('records the experience of an occasion, or its skipping (V11·c)', function () {
    [$setup, $main, $retro] = occasionDiagnosis();
    $leader = $setup['leader'];
    actingAsCustomer($leader);
    $material = situationMaterial($main, $leader);
    app(OccasionService::class)->place($material, $retro, today()->toDateString(), $leader, false);
    $params = [...occasionParams($setup, $main), 'material' => $material];

    Livewire::test(OccasionExperienceForm::class, $params)
        ->assertSee('Az értékek · ezen az alkalmon')
        ->call('skip')
        // Back to the home afterwards (prototype V11·c → V10).
        ->assertRedirect(route('client.diagnosis.show', [$setup['organization'], $main->diagnosis]));

    $experience = OccasionExperience::query()->sole();
    expect($experience->skipped_at)->not->toBeNull()
        ->and($experience->isSubmitted())->toBeFalse();

    $keys = collect(app(ExperienceService::class)->rows($main->valueSetVersion->snapshot))->pluck('key');
    $component = Livewire::test(OccasionExperienceForm::class, $params)
        ->set('answers.'.$keys->first(), '5')
        ->call('submit')
        ->assertHasErrors(['answers']);

    foreach ($keys as $key) {
        $component->set('answers.'.$key, '5');
    }

    $component->call('submit')->assertHasErrors(['effectiveness'])
        ->set('effectiveness', 'partial')
        ->set('note', 'Jó volt.')
        ->call('submit')
        ->assertHasNoErrors();

    $experience->refresh();
    expect($experience->isSubmitted())->toBeTrue()
        ->and($experience->effectiveness)->toBe('partial')
        ->and($experience->note)->toBe('Jó volt.')
        ->and($experience->answers)->toHaveCount($keys->count())
        ->and($experience->value_set_version_id)->toBe($main->value_set_version_id);
    Queue::assertPushed(RecalculateMainDiagnosis::class);
});

it('opens a given experience again from T5·b and returns there (T5·b → V11·c)', function () {
    [$setup, $main, $retro] = occasionDiagnosis();
    $leader = $setup['leader'];
    actingAsCustomer($leader);
    $material = situationMaterial($main, $leader);
    app(OccasionService::class)->place($material, $retro, today()->toDateString(), $leader, false);
    $keys = collect(app(ExperienceService::class)->rows($main->valueSetVersion->snapshot))->pluck('key');
    app(OccasionService::class)->experience($material, $leader, $keys->mapWithKeys(fn ($key) => [$key => '4'])->all(), 'yes', 'Első', skip: false);
    $situations = route('client.diagnosis.situations', occasionParams($setup, $main));
    $form = route('client.diagnosis.occasion-experience', [...occasionParams($setup, $main), 'material' => $material, 'from' => 'helyzeteim']);

    $this->get($situations)->assertOk()
        ->assertSeeHtml('href="'.e($form).'"')
        // The coral plus stays on T5·b while it runs.
        ->assertSeeHtml('href="'.route('client.diagnosis.contribute', occasionParams($setup, $main)).'"');

    $this->get($form)->assertOk()->assertSeeHtml('href="'.$situations.'"');

    Livewire::withQueryParams(['from' => 'helyzeteim'])
        ->test(OccasionExperienceForm::class, [...occasionParams($setup, $main), 'material' => $material])
        ->assertSet('answers.'.$keys->first(), '4')
        ->set('effectiveness', 'partial')
        ->call('submit')
        ->assertRedirect($situations);
});

it('lists the own situations with the missing experience, never anyone else\'s (T5·b)', function () {
    [$setup, $main, $retro] = occasionDiagnosis();
    $leader = $setup['leader'];
    $peer = $setup['peers'][0];
    $own = situationMaterial($main, $leader);
    app(OccasionService::class)->place($own, $retro, today()->toDateString(), $leader, false);
    $peers = situationMaterial($main, $peer, 'A társ saját beszámolója a retróról, amit a vezető nem láthat.');
    app(OccasionService::class)->place($peers, $retro, today()->toDateString(), $peer, false);

    actingAsCustomer($leader);
    Livewire::test(Situations::class, occasionParams($setup, $main))
        ->assertSee('Retró')
        ->assertSee('leírva')
        ->assertSee('megélés hiányzik')
        ->assertSeeHtml(route('client.diagnosis.occasion-experience', [$setup['organization'], $main->diagnosis, $own]))
        ->assertDontSeeHtml($peers->uuid);

    Livewire::test(PlaceMaterial::class, [...occasionParams($setup, $main), 'material' => $peers])
        ->assertNotFound();
});

it('closes the occasions when reading starts', function () {
    [$setup, $main, $retro] = occasionDiagnosis();
    $leader = $setup['leader'];
    $material = situationMaterial($main, $leader);
    app(OccasionService::class)->place($material, $retro, today()->toDateString(), $leader, false);
    app(StageMachine::class)->transition($main, MainDiagnosisStage::Reading, $setup['admin']);
    actingAsCustomer($leader);

    Livewire::test(Contribute::class, occasionParams($setup, $main->refresh()))
        ->assertSee('Az olvasat elindult')
        ->assertDontSee('Feltöltöm');
    Livewire::test(Situation::class, occasionParams($setup, $main))->assertForbidden();
    Livewire::test(OccasionExperienceForm::class, [...occasionParams($setup, $main), 'material' => $material])
        ->call('skip')
        ->assertForbidden();
});

it('keeps a peer who has not started out of the occasions', function () {
    [$setup, $main] = occasionDiagnosis();
    actingAsCustomer($setup['peers'][1]);

    Livewire::test(Contribute::class, occasionParams($setup, $main))->assertForbidden();
});

it('lets the admin add a material to a process and lists its occasions (12 · 12.6)', function () {
    [$setup, $main, $retro] = occasionDiagnosis();
    $own = situationMaterial($main, $setup['leader']);
    app(OccasionService::class)->place($own, $retro, today()->subDays(3)->toDateString(), $setup['leader'], false);
    actingAsAdmin($setup['admin']);

    Livewire::test(ProcessOccasions::class, ['organization' => $setup['organization'], 'process' => $retro])
        ->assertSee(today()->subDays(3)->format('Y.m.d.'))
        ->assertSee($setup['leader']->name)
        ->set('file', UploadedFile::fake()->createWithContent('jegyzet.txt', str_repeat('Az admin jegyzete a retróról. ', 6)))
        ->set('date', today()->toDateString())
        ->call('storeOccasion')
        ->assertHasErrors(['consent'])
        ->set('consent', true)
        ->call('storeOccasion')
        ->assertHasNoErrors()
        ->assertSee('jegyzet.txt');

    $material = Material::query()->where('original_name', 'jegyzet.txt')->sole();
    expect($material->user_id)->toBeNull()
        ->and($material->created_by)->toBe($setup['admin']->id)
        ->and($material->occasion->process_id)->toBe($retro->id)
        ->and($material->parent_diagnosis_id)->toBe($main->diagnosis_id);
});

it('keeps the admin occasions page to the process of the organization', function () {
    [$setup] = occasionDiagnosis();
    $other = Process::factory()->create();
    actingAsAdmin($setup['admin']);

    Livewire::test(ProcessOccasions::class, ['organization' => $setup['organization'], 'process' => $other])->assertNotFound();
});
