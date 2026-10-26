<?php

use App\Enums\MainDiagnosisStage;
use App\Enums\ProcessRhythm;
use App\Enums\ProcessSource;
use App\Livewire\Admin\Organizations\Processes as AdminProcesses;
use App\Livewire\Client\Diagnosis\ProcessList;
use App\Models\AiCall;
use App\Models\Material;
use App\Models\Process;
use App\Services\Ai\AiGateway;
use App\Services\MainDiagnoses\StageMachine;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\StructuredTextResponse;
use Livewire\Livewire;

function importParams(array $setup, $main): array
{
    return ['organization' => $setup['organization'], 'diagnosis' => $main->diagnosis];
}

/**
 * The AI's answer: the processes it read from the text.
 *
 * @param  list<array<string, ?string>>  $processes
 */
function parsedProcesses(array $processes): StructuredTextResponse
{
    $data = ['processes' => array_map(fn (array $process) => ['description' => null, 'rhythm' => null, 'parent_ref' => null, ...$process], $processes)];

    return new StructuredTextResponse($data, json_encode($data), new TextUsage(10, 5), new Meta('anthropic', 'claude-test'));
}

it('adds several processes from a text on V6·d, with descriptions and subprocesses', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    $ops = Process::factory()->for($setup['organization'])->fromCalendar()->create(['name' => 'Ops heti']);
    actingAsCustomer($setup['leader']);
    AiGateway::fake([parsedProcesses([
        ['ref' => 'N1', 'name' => 'Vezetői kör', 'description' => 'A hét prioritásai.', 'rhythm' => 'weekly'],
        ['ref' => 'N2', 'name' => 'Döntések átnézése', 'parent_ref' => 'N1'],
        ['ref' => 'N3', 'name' => 'Hibák átbeszélése', 'parent_ref' => 'E1'],
        ['ref' => 'N4', 'name' => 'Céges buli'],
    ])]);

    Livewire::test(ProcessList::class, importParams($setup, $main))
        ->assertSee('Folyamatok szövegből vagy beszédből')
        ->set('importText', 'Keddenként vezetői kör, ott átnézzük a döntéseket is. Az Ops hetin a hibákat beszéljük át.')
        ->call('parseImport')
        ->assertHasNoErrors()
        ->assertSee('4 folyamatot találtunk.')
        ->assertSet('importRows.1.parent', 'new:r1')
        ->assertSet('importRows.2.parent', 'process:'.$ops->id)
        ->call('removeImportRow', 'r4')
        ->set('importRows.0.name', 'Keddi vezetői kör')
        ->call('importProcesses')
        ->assertHasNoErrors()
        ->assertSet('importRows', [])
        ->assertSee('3 folyamatot felvettünk.');

    $lead = Process::query()->where('name', 'Keddi vezetői kör')->sole();

    expect($lead->description)->toBe('A hét prioritásai.')
        ->and($lead->rhythm)->toBe(ProcessRhythm::Weekly)
        ->and($lead->source)->toBe(ProcessSource::Manual)
        ->and($lead->children()->pluck('name')->all())->toBe(['Döntések átnézése'])
        ->and($ops->children()->pluck('name')->all())->toBe(['Hibák átbeszélése'])
        ->and(Process::query()->where('name', 'Céges buli')->exists())->toBeFalse()
        ->and(AiCall::query()->sole()->organization_id)->toBe($setup['organization']->id);
});

it('does not offer a process the organization already has, and puts its subprocesses under it', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    $existing = Process::factory()->for($setup['organization'])->create(['name' => 'Vezetői kör']);
    actingAsCustomer($setup['leader']);
    AiGateway::fake([parsedProcesses([
        ['ref' => 'N1', 'name' => 'vezetői kör'],
        ['ref' => 'N2', 'name' => 'Döntések átnézése', 'parent_ref' => 'N1'],
    ])]);

    Livewire::test(ProcessList::class, importParams($setup, $main))
        ->set('importText', 'A vezetői körön átnézzük a döntéseket.')
        ->call('parseImport')
        ->assertCount('importRows', 1)
        ->assertSet('importRows.0.parent', 'process:'.$existing->id);
});

it('puts a process the AI nests deeper under the top one of its line', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);
    AiGateway::fake([parsedProcesses([
        ['ref' => 'N1', 'name' => 'Ops heti'],
        ['ref' => 'N2', 'name' => 'Hibák átbeszélése', 'parent_ref' => 'N1'],
        ['ref' => 'N3', 'name' => 'Kritikus hibák', 'parent_ref' => 'N2'],
    ])]);

    Livewire::test(ProcessList::class, importParams($setup, $main))
        ->set('importText', 'Az Ops hetin átbeszéljük a hibákat, azon belül a kritikusakat.')
        ->call('parseImport')
        ->assertSet('importRows.0.parent', null)
        ->assertSet('importRows.1.parent', 'new:r1')
        ->assertSet('importRows.2.parent', 'new:r1');
});

it('refuses a subprocess under a subprocess in the preview', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);
    AiGateway::fake([parsedProcesses([
        ['ref' => 'N1', 'name' => 'Vezetői kör'],
        ['ref' => 'N2', 'name' => 'Döntések átnézése', 'parent_ref' => 'N1'],
    ])]);

    Livewire::test(ProcessList::class, importParams($setup, $main))
        ->set('importText', 'A vezetői körön átnézzük a döntéseket.')
        ->call('parseImport')
        ->set('importRows.0.parent', 'new:r2')
        ->call('importProcesses')
        ->assertHasErrors(['importRows.0.parent'])
        ->assertSee('Alfolyamatnak nem lehet alfolyamata.');

    expect(Process::query()->exists())->toBeFalse();
});

it('says so when the AI cannot read the text', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);
    AiGateway::fake(fn () => throw new RuntimeException('Az AI nem válaszol.'));

    Livewire::test(ProcessList::class, importParams($setup, $main))
        ->set('importText', 'Heti vezetői kör.')
        ->call('parseImport')
        ->assertHasErrors(['importText'])
        ->assertSee('Most nem sikerült feldolgozni a szöveget.')
        ->assertSet('importRows', []);
});

it('says so when the text has no process in it', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    actingAsCustomer($setup['leader']);
    AiGateway::fake([parsedProcesses([])]);

    Livewire::test(ProcessList::class, importParams($setup, $main))
        ->set('importText', 'Szép napunk volt.')
        ->call('parseImport')
        ->assertHasErrors(['importText'])
        ->assertSee('Nem találtunk benne folyamatot.');
});

it('keeps the import closed once the reading has started', function () {
    [$setup, $main] = runningDiagnosis();
    app(StageMachine::class)->transition($main, MainDiagnosisStage::Reading, $setup['admin']);
    actingAsCustomer($setup['leader']);
    AiGateway::fake();

    Livewire::test(ProcessList::class, importParams($setup, $main->refresh()))
        ->assertDontSee('Folyamatok szövegből vagy beszédből')
        ->set('importText', 'Heti vezetői kör.')
        ->call('parseImport')
        ->assertForbidden();
});

it('lets the admin dictate the text and add the processes', function () {
    Storage::fake('materials');
    config(['megis.transcriber' => 'fake']);
    $setup = diagnosisTeam();
    actingAsAdmin($setup['admin']);
    AiGateway::fake([parsedProcesses([
        ['ref' => 'N1', 'name' => 'Szállítási egyeztetés', 'description' => 'A csúszó szállítások az ügyféllel.'],
    ])]);

    Livewire::test(AdminProcesses::class, ['organization' => $setup['organization']])
        ->set('dictationAudio', UploadedFile::fake()->create('felvetel.webm', 200, 'audio/webm'))
        ->call('dictate', 'importText', 34)
        ->call('pollDictations')
        ->assertSet('importText', fn (string $text) => str_contains($text, 'csúszott a szállítás'))
        ->call('parseImport')
        ->call('importProcesses')
        ->assertHasNoErrors();

    expect(Process::query()->sole()->description)->toBe('A csúszó szállítások az ügyféllel.')
        ->and(Material::query()->sole()->user_id)->toBe($setup['admin']->id);
});
