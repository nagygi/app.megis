<?php

use App\Enums\ConsentKind;
use App\Enums\MainDiagnosisStage;
use App\Enums\ParticipantRole;
use App\Enums\StepMark;
use App\Livewire\Client\Diagnosis\Overview;
use App\Mail\DiagnosisNoticeMail;
use App\Models\Consent;
use App\Models\Process;
use App\Services\MainDiagnoses\ExperienceService;
use App\Services\MainDiagnoses\MainDiagnosisService;
use App\Services\MainDiagnoses\StageMachine;
use App\Services\Organizations\OrganizationValueSetService;
use App\Services\Processes\OccasionService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('renders every page of the leader with the frame', function (string $route, ?string $see) {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    $process = Process::factory()->for($setup['organization'])->create(['name' => 'Ops heti']);
    actingAsCustomer($setup['leader']);

    $parameters = ['organization' => $setup['organization'], 'diagnosis' => $main->diagnosis];

    $response = $this->get(route($route, $route === 'client.diagnosis.process' ? [...$parameters, 'process' => $process] : $parameters))->assertOk();

    if ($see !== null) {
        $response->assertSee($see);
    }
})->with([
    'V1' => ['client.diagnosis.show', 'Mi vár rád'],
    'V2' => ['client.diagnosis.sajat-ertekek', '1 / 6'],
    'V3' => ['client.diagnosis.szervezet-ertekei', 'A szervezet értékei'],
    'V4' => ['client.diagnosis.markad-alapja', 'Mit mond rólatok a fa?'],
    'V5' => ['client.diagnosis.megeles', 'Hogyan látjátok ma a márkát?'],
    'V6' => ['client.diagnosis.folyamatok', 'Milyen folyamataid vannak?'],
    'V6·b' => ['client.diagnosis.process-calendar', 'Mit olvasunk, és mit soha.'],
    'V6·c' => ['client.diagnosis.process-conversation', 'Négy kérdés, a saját szavaidból.'],
    'V6·d' => ['client.diagnosis.process-list', 'Detektált folyamatok'],
    'V6·e' => ['client.diagnosis.process', 'Ops heti'],
    'V6·f' => ['client.diagnosis.process-summary', 'A folyamataid'],
    'V9' => ['client.diagnosis.ki-lat-mit', 'Egy lap, a diagnózis végéig érvényes.'],
]);

it('renders the peer\'s pages from the invitation to the home', function () {
    $setup = diagnosisTeam(peers: 1);
    $main = startMainDiagnosis($setup);
    confirmOwnValues($setup['leader']);
    $version = app(OrganizationValueSetService::class)->forOrganization($setup['organization'])->latestVersion;
    $answers = collect(app(ExperienceService::class)->rows($version->snapshot))->mapWithKeys(fn ($row) => [$row['key'] => '4'])->all();
    app(ExperienceService::class)->submit($main, $setup['leader'], $version, $answers);
    $main->stepMarks()->create(['user_id' => $setup['leader']->id, 'step' => 'processes', 'mark' => StepMark::Skipped]);
    app(MainDiagnosisService::class)->start($main->refresh(), $setup['leader']);
    $peer = $setup['peers'][0];
    $parameters = ['organization' => $setup['organization'], 'diagnosis' => $main->diagnosis];
    actingAsCustomer($peer);

    $this->get(route('client.diagnosis.show', $parameters))->assertOk()->assertSee('Hogyan látod te?');

    Consent::create(['user_id' => $peer->id, 'main_diagnosis_id' => $main->id, 'kind' => ConsentKind::Answers, 'text_version' => 'v', 'text' => 't', 'given_at' => now()]);
    $main->participantFor($peer, ParticipantRole::Peer)->update(['started_at' => now()]);

    $this->get(route('client.diagnosis.show', $parameters))->assertOk()->assertSee('Két lépés most, egy, amíg fut.');
    $this->get(route('client.diagnosis.sajat-ertekek', $parameters))->assertOk()->assertSee('1 / 2');
    $this->get(route('client.diagnosis.megeles', $parameters))->assertOk()->assertSee('2 / 2');
    $this->get(route('client.diagnosis.ki-lat-mit', $parameters))->assertForbidden();
    // The peer's home is the diagnosis they take part in (26 · 26.4).
    $this->get(route('client.home', $setup['organization']))->assertRedirect(route('client.diagnosis.show', $parameters));
});

it('renders the occasion pages of a running diagnosis (12)', function () {
    Storage::fake('materials');
    Queue::fake();
    [$setup, $main] = runningDiagnosis();
    $process = Process::factory()->accepted()->for($setup['organization'])->create(['name' => 'Heti retró']);
    $material = app(OccasionService::class)->situationText($main, $setup['leader'], 'A heti retrón mindenki elmondta, mi akadt el a héten.');
    $parameters = ['organization' => $setup['organization'], 'diagnosis' => $main->diagnosis];
    actingAsCustomer($setup['leader']);

    $this->get(route('client.diagnosis.contribute', $parameters))->assertOk()->assertSee('Hozzáteszek')->assertSee('nincs besorolva');
    $this->get(route('client.diagnosis.situation', $parameters))->assertOk()->assertSee('Mesélj el egy alkalmat');
    $this->get(route('client.diagnosis.place', [...$parameters, 'material' => $material]))->assertOk()->assertSee('Melyik rutinhoz kötődik?')->assertSee('Heti retró');

    app(OccasionService::class)->place($material, $process, today()->toDateString(), $setup['leader'], false);

    $this->get(route('client.diagnosis.place', [...$parameters, 'material' => $material]))->assertRedirect(route('client.diagnosis.contribute', $parameters));
    $this->get(route('client.diagnosis.occasion-experience', [...$parameters, 'material' => $material]))->assertOk()->assertSee('Heti retró · '.today()->format('Y.m.d.').' · Egy helyzet');
    $this->get(route('client.diagnosis.situations', $parameters))->assertOk()->assertSee('megélés hiányzik');
});

it('shows the leader\'s home while it runs, with the coral plus, and without it in reading (26 · 26.1, 26.2)', function () {
    Queue::fake();
    [$setup, $main] = runningDiagnosis();
    $parameters = ['organization' => $setup['organization'], 'diagnosis' => $main->diagnosis];
    actingAsCustomer($setup['leader']);

    $this->get(route('client.home', $setup['organization']))->assertRedirect(route('client.diagnosis.show', $parameters));
    $this->get(route('client.diagnosis.show', $parameters))
        ->assertOk()
        ->assertSee('A ti diagnózisotok')
        ->assertSee('Hol tartunk')
        ->assertSee('0 / 2 kitöltötte')
        ->assertSee('Még 3 társ hangja kell, hogy lássátok a rést.')
        ->assertSee('0 felvétel · 0 dokumentum · 0 elmondott helyzet')
        ->assertSeeHtml('href="'.route('client.diagnosis.contribute', $parameters).'"');

    app(StageMachine::class)->transition($main, MainDiagnosisStage::Reading, $setup['admin']);

    $this->get(route('client.diagnosis.show', $parameters))
        ->assertOk()
        ->assertSee('A mégis olvasata')
        ->assertDontSeeHtml('href="'.route('client.diagnosis.contribute', $parameters).'"');
});

it('lets the leader remind the peers from the home, without names (26, question 2)', function () {
    Mail::fake();
    Queue::fake();
    [$setup, $main] = runningDiagnosis();
    actingAsCustomer($setup['leader']);

    Livewire::test(Overview::class, ['organization' => $setup['organization'], 'diagnosis' => $main->diagnosis])
        ->call('remindPeers')
        ->assertDontSee($setup['peers'][0]->name);

    Mail::assertQueued(DiagnosisNoticeMail::class, 2);
});
