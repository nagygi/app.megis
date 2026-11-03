<?php

use App\Enums\ConsentKind;
use App\Enums\DiagnosisStep;
use App\Enums\MainDiagnosisStage;
use App\Enums\MaterialContext;
use App\Enums\ParticipantRole;
use App\Enums\StepMark;
use App\Jobs\DetectCapabilities;
use App\Livewire\Client\Diagnosis\Capabilities;
use App\Models\CapabilityStory;
use App\Models\Consent;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Services\MainDiagnoses\StepProgress;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function capabilityParams(array $setup, MainDiagnosis $main): array
{
    return ['organization' => $setup['organization'], 'diagnosis' => $main->diagnosis];
}

/**
 * A situation long enough to count (min_characters).
 */
function toldSituation(string $start = 'Kedden'): string
{
    return $start.' a Vezetői körön csak a végén derült ki, hogy a pénteki átadás csúszik, pedig hétfőn már tudtuk.';
}

beforeEach(function () {
    config(['megis.capabilities.measurement.enabled' => true]);
});

it('leaves Képességek out while the capability measurement is off', function () {
    config(['megis.capabilities.measurement.enabled' => false]);
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);

    expect($main->leaderSteps())->not->toContain(DiagnosisStep::Capabilities)
        ->and($main->peerSteps())->not->toContain(DiagnosisStep::Capabilities);

    $this->get(route('client.diagnosis.show', capabilityParams($setup, $main)))->assertOk()->assertSee('Hat lépés, és indul a diagnózis.');
    $this->get(route('client.diagnosis.kepessegek', capabilityParams($setup, $main)))->assertNotFound();
});

it('puts Képességek after the Megélés while the capability measurement runs', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);

    expect(array_map(fn (DiagnosisStep $step) => $step->value, $main->leaderSteps()))
        ->toBe(['own_values', 'organization_values', 'brand', 'experience', 'capabilities', 'processes', 'visibility'])
        ->and($main->peerSteps())->toContain(DiagnosisStep::Capabilities)
        ->and($main->peerMinutes())->toBe(11);

    $this->get(route('client.diagnosis.show', capabilityParams($setup, $main)))
        ->assertOk()
        ->assertSee('Hét lépés, és indul a diagnózis.')
        ->assertSee('kb. 16 perc')
        ->assertSee('Képességek')
        ->assertSee('amire építhettek, és ami hiányzik');

    // The product's own switch turns it off too.
    config(['diagnosis.products.full.capability_measurement' => false]);
    expect($main->leaderSteps())->not->toContain(DiagnosisStep::Capabilities);
});

it('asks the situations in one text, without value cards', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);

    $this->get(route('client.diagnosis.kepessegek', capabilityParams($setup, $main)))
        ->assertOk()
        ->assertSee('Hol lesz az értékeitekből tett?')
        ->assertSee('Egy szöveg · leírva, elmondva vagy feltöltve')
        ->assertSee('Mi történt nálatok mostanában?')
        ->assertDontSee('Másik értékről mesélnék')
        ->assertDontSee('Még egy helyzet');

    expect(CapabilityStory::query()->count())->toBe(0);
});

it('sends the told text as one material the capability measurement reads', function () {
    Queue::fake();
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);

    Livewire::test(Capabilities::class, capabilityParams($setup, $main))
        ->set('answer', toldSituation())
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('client.diagnosis.folyamatok', capabilityParams($setup, $main)));

    $material = Material::query()->where('context', MaterialContext::CapabilityStory)->sole();
    expect($material->parent_diagnosis_id)->toBe($main->diagnosis_id)
        ->and($material->user_id)->toBe($setup['leader']->id)
        ->and($material->segments)->toHaveCount(2)
        ->and($material->segments[0]['speaker'])->toBe('mégis (kérdés)')
        ->and($material->segments[0]['text'])->toStartWith('Mi történt nálatok mostanában?')
        ->and($material->segments[1])->toMatchArray(['speaker' => 'Vezető', 'text' => toldSituation()]);
    expect(CapabilityStory::query()->sole()->material_id)->toBe($material->id);
    expect(app(StepProgress::class)->forLeader($main, $setup['leader'])->get(DiagnosisStep::Capabilities)?->done)->toBeTrue();
    Queue::assertPushed(DetectCapabilities::class, fn (DetectCapabilities $job) => $job->material->is($material));
});

it('needs a few sentences within the limit, or "Kihagyom"', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);

    $component = Livewire::test(Capabilities::class, capabilityParams($setup, $main));

    $component->set('answer', str_repeat('a', 10001))->assertHasErrors(['answer' => 'max']);
    $component->set('answer', 'Rövid.')->call('submit')->assertHasErrors(['answer'])->assertSee('Mesélj el legalább egy helyzetet, legalább 40 karakterben, vagy hagyd ki a lépést.');
    expect(Material::query()->count())->toBe(0);
    expect(CapabilityStory::query()->sole()->answer)->toBe('Rövid.');

    $component->call('skip')->assertRedirect(route('client.diagnosis.folyamatok', capabilityParams($setup, $main)));
    expect($main->stepMarks()->where('step', DiagnosisStep::Capabilities)->sole()->mark)->toBe(StepMark::Skipped);
    expect(app(StepProgress::class)->forLeader($main, $setup['leader'])->get(DiagnosisStep::Capabilities)?->done)->toBeTrue();
});

it('measures a changed text again and takes an emptied one back', function () {
    Queue::fake();
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);
    Livewire::test(Capabilities::class, capabilityParams($setup, $main))->set('answer', toldSituation())->call('submit');
    $material = Material::query()->where('context', MaterialContext::CapabilityStory)->sole();

    Livewire::test(Capabilities::class, capabilityParams($setup, $main))
        ->assertSet('answer', toldSituation())
        ->set('answer', toldSituation('Szerdán'))
        ->call('submit');

    expect($material->fresh()->segments[1]['text'])->toBe(toldSituation('Szerdán'));
    Queue::assertPushed(DetectCapabilities::class, fn (DetectCapabilities $job) => $job->material->is($material) && $job->force);

    Livewire::test(Capabilities::class, capabilityParams($setup, $main))
        ->set('answer', '')
        ->call('submit')
        ->assertHasErrors(['answer']);

    expect(Material::query()->where('context', MaterialContext::CapabilityStory)->exists())->toBeFalse();
    expect(CapabilityStory::query()->sole()->submitted_at)->toBeNull();
    expect(app(StepProgress::class)->forLeader($main, $setup['leader'])->get(DiagnosisStep::Capabilities)?->done)->toBeFalse();
});

it('puts a dictation into the text, editable', function () {
    Storage::fake('materials');
    config(['megis.transcriber' => 'fake']);
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);

    Livewire::test(Capabilities::class, capabilityParams($setup, $main))
        ->set('dictationAudio', UploadedFile::fake()->create('felvetel.webm', 200, 'audio/webm'))
        ->call('dictate', 'answer', 21)
        ->call('pollDictations')
        ->assertSet('answer', fn (string $text) => str_contains($text, 'csúszott a szállítás'));

    expect(CapabilityStory::query()->sole()->answer)->toContain('csúszott a szállítás');
    expect(Material::query()->sole()->context)->toBe(MaterialContext::Dictation);
});

it('lets a peer tell their situations once started, the leader only counts them', function () {
    Queue::fake();
    [$setup, $main] = runningDiagnosis();
    $peer = $setup['peers'][0];
    $main->participantFor($peer, ParticipantRole::Peer)->update(['started_at' => now()]);
    Consent::create(['user_id' => $peer->id, 'main_diagnosis_id' => $main->id, 'kind' => ConsentKind::Answers, 'text_version' => 'v', 'text' => 't', 'given_at' => now()]);
    actingAsCustomer($peer);

    $this->get(route('client.diagnosis.show', capabilityParams($setup, $main)))
        ->assertOk()
        ->assertSee('Három lépés most, egy amíg fut.')
        ->assertSee('Képességek');

    Livewire::test(Capabilities::class, capabilityParams($setup, $main))
        ->assertSee('A vezető nem látja, mit írtál.')
        ->set('answer', toldSituation())
        ->call('submit')
        ->assertHasNoErrors();

    expect(Material::query()->where('context', MaterialContext::CapabilityStory)->sole()->segments[1]['speaker'])->toBe('Társ');

    actingAsCustomer($setup['leader']);
    $this->get(route('client.diagnosis.show', capabilityParams($setup, $main)))
        ->assertOk()
        ->assertSee('1 kitöltő mesélt · ebből 1 társ')
        ->assertDontSee(toldSituation());
});

it('keeps the text read-only from the reading on', function () {
    [$setup, $main] = runningDiagnosis();
    $main->update(['stage' => MainDiagnosisStage::Reading]);
    actingAsCustomer($setup['leader']);

    Livewire::test(Capabilities::class, capabilityParams($setup, $main))
        ->assertSee('Ez a lépés már nem módosítható.')
        ->call('skip')
        ->assertForbidden();
});
