<?php

use App\Enums\MainDiagnosisStage;
use App\Livewire\Client\ProcessSheet;
use App\Models\Process;
use App\Services\Ai\AiGateway;
use App\Services\MainDiagnoses\StageMachine;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\StructuredTextResponse;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * The leader's data sheet of a process: its description (2000 characters)
 * is written, told or uploaded.
 *
 * @return array{0: Testable, 1: Process}
 */
function descriptionSheet(?string $description = null): array
{
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    $process = Process::factory()->for($setup['organization'])->create(['description' => $description]);
    actingAsCustomer($setup['leader']);

    return [
        Livewire::test(ProcessSheet::class, ['organization' => $setup['organization'], 'diagnosis' => $main->diagnosis, 'process' => $process]),
        $process,
    ];
}

function condensedText(string $text): StructuredTextResponse
{
    return new StructuredTextResponse(['text' => $text], json_encode(['text' => $text]), new TextUsage(10, 5), new Meta('anthropic', 'claude-test'));
}

it('puts the text of an uploaded document after the description and saves it', function () {
    [$component, $process] = descriptionSheet('Havi zárás.');

    $component->set('dictationFile', UploadedFile::fake()->createWithContent('leiras.txt', "A pénzügy zárja a hónapot.\nA vezető jóváhagyja."))
        ->call('readFile', 'description');

    $component->assertHasNoErrors()
        ->assertSet('description', "Havi zárás. A pénzügy zárja a hónapot.\nA vezető jóváhagyja.")
        ->assertSet('dictationFile', null)
        ->assertDispatched('dictation-applied', field: 'description', notice: null);
    expect($process->refresh()->description)->toBe("Havi zárás. A pénzügy zárja a hónapot.\nA vezető jóváhagyja.");
});

it('condenses a document that does not fit into the room left in the field', function () {
    $asked = [];
    AiGateway::fake(function (string $prompt) use (&$asked) {
        $asked[] = json_decode($prompt, true);

        return condensedText('Havonta zárjuk a könyveket.');
    });
    [$component, $process] = descriptionSheet('Havi zárás.');

    $component->set('dictationFile', UploadedFile::fake()->createWithContent('szabalyzat.txt', str_repeat('A zárás lépései. ', 150)))
        ->call('readFile', 'description');

    $component->assertHasNoErrors()
        ->assertSet('description', 'Havi zárás. Havonta zárjuk a könyveket.')
        ->assertDispatched('dictation-applied', field: 'description', notice: "A fájl szövege 2\u{A0}549 karakter volt, nem fért el: az AI 27 karakterre tömörítette. Nézd át.");
    expect($asked)->toBe([['szöveg' => trim(str_repeat('A zárás lépései. ', 150)), 'legfeljebb karakter' => 1789]])
        ->and($process->refresh()->description)->toBe('Havi zárás. Havonta zárjuk a könyveket.');
});

it('condenses a transcript together with the description when little room is left', function () {
    Storage::fake('materials');
    config(['megis.transcriber' => 'fake']);
    $asked = [];
    AiGateway::fake(function (string $prompt) use (&$asked) {
        $asked[] = json_decode($prompt, true)['legfeljebb karakter'];

        return condensedText('Összevont leírás.');
    });
    [$component, $process] = descriptionSheet(str_repeat('a', 1900));

    $component->set('dictationAudio', UploadedFile::fake()->create('felvetel.webm', 200, 'audio/webm'))
        ->call('dictate', 'description', 34)
        ->call('pollDictations');

    $component->assertSet('description', 'Összevont leírás.')
        ->assertDispatched('dictation-applied', field: 'description', notice: 'Nem fért el, ezért az AI a meglévő szöveggel együtt 17 karakterre tömörítette. Nézd át.');
    expect($asked)->toBe([1800])
        ->and($process->refresh()->description)->toBe('Összevont leírás.');
});

it('never puts more than the room into the field, even when the AI answers longer', function () {
    AiGateway::fake([condensedText(str_repeat('c', 600))]);
    [$component, $process] = descriptionSheet(str_repeat('a', 1500));

    $component->set('dictationFile', UploadedFile::fake()->createWithContent('hosszu.txt', str_repeat('b', 900)))
        ->call('readFile', 'description');

    $component->assertSet('description', str_repeat('a', 1500).' '.str_repeat('c', 499))
        ->assertDispatched('dictation-applied', field: 'description', notice: 'A fájl szövege 900 karakter volt, nem fért el: az AI 499 karakterre tömörítette. Nézd át.');
    expect(mb_strlen($process->refresh()->description))->toBe(2000);
});

it('cuts the text at the limit when the AI cannot condense it', function () {
    Exceptions::fake();
    AiGateway::fake(fn () => throw new RuntimeException('Az AI nem válaszol.'));
    [$component, $process] = descriptionSheet();

    $component->set('dictationFile', UploadedFile::fake()->createWithContent('hosszu.txt', str_repeat('b', 2500)))
        ->call('readFile', 'description');

    $component->assertSet('description', str_repeat('b', 2000))
        ->assertDispatched('dictation-applied', field: 'description', notice: "Nem fért el, és az AI most nem tudta tömöríteni: az első 2\u{A0}000 karakter maradt meg. Nézd át.");
    Exceptions::assertReported(RuntimeException::class);
    expect($process->refresh()->description)->toBe(str_repeat('b', 2000));
});

it('shows why a file cannot be read under the field and leaves the description', function (string $name, string $content, string $message) {
    [$component, $process] = descriptionSheet('Havi zárás.');

    $component->set('dictationFile', UploadedFile::fake()->createWithContent($name, $content))
        ->call('readFile', 'description');

    $component->assertSet('description', 'Havi zárás.')
        ->assertNotDispatched('dictation-applied');
    expect($component->errors()->first('description'))->toBe($message)
        ->and($process->refresh()->description)->toBe('Havi zárás.');
})->with([
    'a .doc file' => ['leiras.doc', 'Régi Word fájl', 'A(z) fájl kiterjesztése ezek egyike lehet: txt, md, docx, pdf.'],
    'a file without text' => ['ures.txt', " \n ", 'A fájlban nincs szöveg.'],
]);

it('does not read a file into the description once the reading has started', function () {
    [$setup, $main] = runningDiagnosis();
    $process = Process::factory()->for($setup['organization'])->create(['description' => 'A hét hibái.']);
    app(StageMachine::class)->transition($main, MainDiagnosisStage::Reading, $setup['admin']);
    actingAsCustomer($setup['leader']);

    Livewire::test(ProcessSheet::class, ['organization' => $setup['organization'], 'diagnosis' => $main->diagnosis, 'process' => $process])
        ->set('dictationFile', UploadedFile::fake()->createWithContent('leiras.txt', 'Más leírás'))
        ->call('readFile', 'description')
        ->assertForbidden();

    expect($process->refresh()->description)->toBe('A hét hibái.');
});

it('offers writing, telling and uploading on the description of an editable sheet', function () {
    [$component] = descriptionSheet();

    $component->assertOk()
        ->assertSeeInOrder(['Leírom', 'Elmondom', 'Feltöltöm'])
        ->assertSee('Formátum: txt, md, docx, pdf · legfeljebb 10 MB.');
});
