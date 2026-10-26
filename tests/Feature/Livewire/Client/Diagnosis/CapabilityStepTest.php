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
use App\Models\Product;
use App\Models\User;
use App\Services\MainDiagnoses\ExperienceService;
use App\Services\MainDiagnoses\StepProgress;
use App\Services\Organizations\OrganizationValueSetService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * The Megélés of a user, by the position of the organization's value: "6", "2"…
 *
 * @param  list<string>  $answers
 */
function experienceOf(MainDiagnosis $main, User $user, array $answers): void
{
    $experiences = app(ExperienceService::class);
    $version = $main->valueSetVersion ?? app(OrganizationValueSetService::class)->forOrganization($main->diagnosis->organization)->latestVersion;
    $rows = collect($experiences->rows($version->snapshot));

    $experiences->submit($main, $user, $version, $rows->values()->mapWithKeys(fn ($row, $index) => [$row['key'] => $answers[$index] ?? '4'])->all());
}

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

    $this->get(route('client.diagnosis.show', capabilityParams($setup, $main)))->assertOk()->assertSee('6 lépés, és indul a diagnózis.');
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
        ->assertSee('7 lépés, és indul a diagnózis.')
        ->assertSee('kb. 16 perc')
        ->assertSee('Képességek')
        ->assertSee('amire építhettek, és ami hiányzik');

    // The product's own switch turns it off too.
    config(['diagnosis.products.full.capability_measurement' => false]);
    expect($main->leaderSteps())->not->toContain(DiagnosisStep::Capabilities);
});

it('suggests three situations in a Teljes: the best and the worst lived value, and an own one', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    $own = confirmOwnValues($setup['leader'])->items()->sole();
    experienceOf($main, $setup['leader'], ['6', '2', '4']);
    $values = app(OrganizationValueSetService::class)->forOrganization($setup['organization'])->items()->orderBy('position')->get();
    actingAsCustomer($setup['leader']);

    Livewire::test(Capabilities::class, capabilityParams($setup, $main))
        ->assertOk()
        ->assertSee('Hol lesz az értékeitekből tett?')
        ->assertSee('3 helyzet · leírva vagy elmondva')
        ->assertSee('Mikor került valamibe nemrég ez az érték?')
        ->assertSee('Mikor kellett volna nemrég ez az érték, és nem úgy ment?')
        ->assertSee('Mikor tartottad magad nemrég ehhez az értékhez');

    expect(CapabilityStory::query()->orderBy('position')->get(['value_name', 'kind', 'value_source'])->toArray())->toBe([
        ['value_name' => $values[0]->name, 'kind' => CapabilityStory::KIND_TESTED, 'value_source' => CapabilityStory::SOURCE_ORGANIZATION],
        ['value_name' => $values[1]->name, 'kind' => CapabilityStory::KIND_MISSED, 'value_source' => CapabilityStory::SOURCE_ORGANIZATION],
        ['value_name' => $own->name, 'kind' => CapabilityStory::KIND_OWN, 'value_source' => CapabilityStory::SOURCE_OWN],
    ]);
});

it('suggests one situation in a snapshot, the worst lived value', function () {
    $setup = diagnosisTeam(Product::SNAPSHOT_INTERNAL, peers: 0);
    $main = startMainDiagnosis($setup);
    experienceOf($main, $setup['leader'], ['5', '6', '1']);
    actingAsCustomer($setup['leader']);

    Livewire::test(Capabilities::class, capabilityParams($setup, $main))->assertSee('1 helyzet · leírva vagy elmondva');

    $story = CapabilityStory::query()->sole();
    expect($story->kind)->toBe(CapabilityStory::KIND_MISSED)
        ->and($story->value_name)->toBe(app(OrganizationValueSetService::class)->forOrganization($setup['organization'])->items()->orderBy('position')->get()[2]->name);
});

it('sends the told situations as materials the capability measurement reads', function () {
    Queue::fake();
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    experienceOf($main, $setup['leader'], ['6', '2', '4']);
    actingAsCustomer($setup['leader']);

    $component = Livewire::test(Capabilities::class, capabilityParams($setup, $main));
    [$first, $second] = CapabilityStory::query()->orderBy('position')->get();

    $component->set('answers.'.$first->id, toldSituation())
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('client.diagnosis.folyamatok', capabilityParams($setup, $main)));

    $material = Material::query()->where('context', MaterialContext::CapabilityStory)->sole();
    expect($material->parent_diagnosis_id)->toBe($main->diagnosis_id)
        ->and($material->user_id)->toBe($setup['leader']->id)
        ->and($material->segments)->toHaveCount(2)
        ->and($material->segments[0]['speaker'])->toBe('mégis (kérdés)')
        ->and($material->segments[0]['text'])->toContain($first->value_name)->toContain('Mikor került valamibe nemrég ez az érték?')
        ->and($material->segments[1])->toMatchArray(['speaker' => 'Vezető', 'text' => toldSituation()])
        ->and($first->fresh()->material_id)->toBe($material->id)
        ->and($second->fresh()->submitted_at)->toBeNull()
        ->and(app(StepProgress::class)->forLeader($main, $setup['leader'])->get(DiagnosisStep::Capabilities)?->done)->toBeTrue();

    Queue::assertPushed(DetectCapabilities::class, fn (DetectCapabilities $job) => $job->material->is($material));
});

it('needs one situation of a few sentences, or "Kihagyom"', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);

    $component = Livewire::test(Capabilities::class, capabilityParams($setup, $main));
    $story = CapabilityStory::query()->orderBy('position')->first();

    $component->set('answers.'.$story->id, 'Rövid.')->call('submit')->assertHasErrors(['answers']);
    expect(Material::query()->count())->toBe(0);

    $component->call('skip')->assertRedirect(route('client.diagnosis.folyamatok', capabilityParams($setup, $main)));
    expect($main->stepMarks()->where('step', DiagnosisStep::Capabilities)->sole()->mark)->toBe(StepMark::Skipped)
        ->and(app(StepProgress::class)->forLeader($main, $setup['leader'])->get(DiagnosisStep::Capabilities)?->done)->toBeTrue();
});

it('measures a changed situation again and takes a removed one back', function () {
    Queue::fake();
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);

    $component = Livewire::test(Capabilities::class, capabilityParams($setup, $main));
    [$first, $second] = CapabilityStory::query()->orderBy('position')->get();
    $component->set('answers.'.$first->id, toldSituation())->set('answers.'.$second->id, toldSituation('Pénteken'))->call('submit');
    $material = $first->fresh()->material;

    Livewire::test(Capabilities::class, capabilityParams($setup, $main))
        ->set('answers.'.$first->id, toldSituation('Szerdán'))
        ->call('submit');

    expect($material->fresh()->segments[1]['text'])->toBe(toldSituation('Szerdán'))
        ->and(Material::query()->where('context', MaterialContext::CapabilityStory)->count())->toBe(2);
    Queue::assertPushed(DetectCapabilities::class, fn (DetectCapabilities $job) => $job->material->is($material) && $job->force);

    Livewire::test(Capabilities::class, capabilityParams($setup, $main))->call('removeStory', $second->id);

    expect(CapabilityStory::query()->whereKey($second->id)->exists())->toBeFalse()
        ->and(Material::query()->where('context', MaterialContext::CapabilityStory)->count())->toBe(1);
});

it('puts a dictation into the card, editable', function () {
    Storage::fake('materials');
    config(['megis.transcriber' => 'fake']);
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);

    $component = Livewire::test(Capabilities::class, capabilityParams($setup, $main));
    $story = CapabilityStory::query()->orderBy('position')->first();

    $component->set('dictationAudio', UploadedFile::fake()->create('felvetel.webm', 200, 'audio/webm'))
        ->call('dictate', 'answers.'.$story->id, 21)
        ->call('pollDictations')
        ->assertSet('answers.'.$story->id, fn (string $text) => str_contains($text, 'csúszott a szállítás'));

    expect($story->fresh()->answer)->toContain('csúszott a szállítás')
        ->and(Material::query()->sole()->context)->toBe(MaterialContext::Dictation);
});

it('lets a peer tell one situation once started, the leader only counts them', function () {
    Queue::fake();
    [$setup, $main] = runningDiagnosis();
    $peer = $setup['peers'][0];
    $main->participantFor($peer, ParticipantRole::Peer)->update(['started_at' => now()]);
    Consent::create(['user_id' => $peer->id, 'main_diagnosis_id' => $main->id, 'kind' => ConsentKind::Answers, 'text_version' => 'v', 'text' => 't', 'given_at' => now()]);
    actingAsCustomer($peer);

    $this->get(route('client.diagnosis.show', capabilityParams($setup, $main)))
        ->assertOk()
        ->assertSee('Három lépés most, egy, amíg fut.')
        ->assertSee('Képességek');

    $component = Livewire::test(Capabilities::class, capabilityParams($setup, $main))->assertSee('A vezető nem látja, mit írtál.');
    $story = CapabilityStory::query()->sole();
    $component->set('answers.'.$story->id, toldSituation())->call('submit')->assertHasNoErrors();

    expect(Material::query()->where('context', MaterialContext::CapabilityStory)->sole()->segments[1]['speaker'])->toBe('Társ');

    actingAsCustomer($setup['leader']);
    $this->get(route('client.diagnosis.show', capabilityParams($setup, $main)))
        ->assertOk()
        ->assertSee('1 helyzet elmondva · ebből 1 a társaktól')
        ->assertDontSee(toldSituation());
});

it('keeps the situations read-only from the reading on', function () {
    [$setup, $main] = runningDiagnosis();
    $main->update(['stage' => MainDiagnosisStage::Reading]);
    actingAsCustomer($setup['leader']);

    Livewire::test(Capabilities::class, capabilityParams($setup, $main))
        ->assertSee('Ez a lépés már nem módosítható.')
        ->call('skip')
        ->assertForbidden();
});
