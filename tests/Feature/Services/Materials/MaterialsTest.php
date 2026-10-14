<?php

use App\Enums\CheckStatus;
use App\Exceptions\UnreadableMaterialException;
use App\Models\AiCall;
use App\Services\Checks\TranscriptionCheck;
use App\Services\Materials\AudioInspector;
use App\Services\Materials\TextExtractor;
use Illuminate\Support\Facades\Process;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TranscriptionSegment;
use Laravel\Ai\Responses\Data\TranscriptionUsage;
use Laravel\Ai\Responses\TranscriptionResponse;
use Laravel\Ai\Transcription;
use PhpOffice\PhpWord\PhpWord;

it('reads the text of a docx file', function () {
    $word = new PhpWord;
    $section = $word->addSection();
    $section->addText('Anna: Csúszunk.');
    $section->addText('Péter: Szóljunk most.');
    $path = tempnam(sys_get_temp_dir(), 'docx').'.docx';
    $word->save($path, 'Word2007');

    expect(app(TextExtractor::class)->extract($path, 'docx'))->toBe("Anna: Csúszunk.\nPéter: Szóljunk most.");
});

it('reads an old Windows-1250 text file as UTF-8', function () {
    $path = tempnam(sys_get_temp_dir(), 'txt');
    file_put_contents($path, iconv('UTF-8', 'Windows-1250', 'Őszinte szó, ügyfél.'));

    expect(app(TextExtractor::class)->extract($path, 'txt'))->toBe('Őszinte szó, ügyfél.');
});

it('refuses doc and empty files with a message for the user', function (string $extension, string $content, string $message) {
    $path = tempnam(sys_get_temp_dir(), 'file');
    file_put_contents($path, $content);

    expect(fn () => app(TextExtractor::class)->extract($path, $extension))
        ->toThrow(UnreadableMaterialException::class, $message);
})->with([
    'doc' => ['doc', 'régi', 'docx-ként'],
    'empty' => ['txt', "  \n ", 'nincs szöveg'],
]);

it('measures audio with ffprobe', function () {
    Process::fake(['*' => Process::result(output: "74.32\n")]);

    expect(app(AudioInspector::class)->durationSeconds('/tmp/audio.webm'))->toBe(75);
});

it('warns when the transcription model or ffprobe is missing, and refuses the sample transcriber in production', function () {
    Process::fake(['*' => Process::result(exitCode: 1)]);
    config(['megis.transcriber' => 'ai', 'ai.tasks.transcription.model' => null]);

    $result = app(TranscriptionCheck::class)->run();
    expect($result->status)->toBe(CheckStatus::Warning)
        ->and($result->message)->toContain('AI_TRANSCRIPTION_MODEL')->toContain('ffprobe');

    $this->app['env'] = 'production';
    config(['megis.transcriber' => 'fake']);
    expect(app(TranscriptionCheck::class)->run()->status)->toBe(CheckStatus::Error);
});

it('compares transcription providers on one recording and logs the calls', function () {
    $path = tempnam(sys_get_temp_dir(), 'audio').'.mp3';
    file_put_contents($path, 'hang');
    Transcription::fake([
        new TranscriptionResponse('Kezdjük. Jó.', collect([
            new TranscriptionSegment('Kezdjük.', 'A', 0, 2),
            new TranscriptionSegment('Jó.', 'B', 2, 3),
        ]), new TranscriptionUsage(audioSeconds: 180), new Meta('openai', 'gpt-test')),
    ]);

    $this->artisan('megis:transcription:compare', ['file' => $path, '--model' => ['openai:gpt-test']])
        ->expectsOutputToContain('openai:gpt-test')
        ->assertSuccessful();

    expect(AiCall::query()->sole())
        ->task->toBe('transcription')
        ->audio_minutes->toBe(3.0);
});

it('prunes expired anonymous mirrors from the console', function () {
    $this->artisan('megis:mirror:prune')->expectsOutputToContain('0 lejárt névtelen tükör törölve.')->assertSuccessful();
});
