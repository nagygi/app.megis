<?php

use App\Enums\MaterialContext;
use App\Enums\MaterialSource;
use App\Enums\MaterialStatus;
use App\Models\Material;
use App\Models\Process;

/*
 * Feltöltöm itself is not driven here: the browser test server keeps the
 * multipart field "files[]" flat, so a Livewire upload never finishes. The
 * file's way into the field is covered in WithDictationTest; the answer comes
 * back to the browser the same way as a transcript.
 */
it('switches the description between Leírom, Elmondom and Feltöltöm, and shows an arrived transcript to edit', function () {
    $setup = diagnosisTeam();
    $main = startMainDiagnosis($setup);
    $process = Process::factory()->for($setup['organization'])->create(['name' => 'Havi pénzügyi zárás', 'description' => null]);
    $transcript = Material::factory()->create([
        'context' => MaterialContext::Dictation,
        'source' => MaterialSource::Recording,
        'user_id' => $setup['leader']->id,
        'organization_id' => $setup['organization']->id,
        'text' => 'A pénzügy zárja a hónapot, a vezető jóváhagyja.',
        'status' => MaterialStatus::Processed,
    ]);

    $page = loginInBrowser($setup['leader']);
    $page->navigate(route('client.diagnosis.process', [$setup['organization'], $main->diagnosis, $process]));
    $page->assertVisible('#description')->assertNoAccessibilityIssues();

    $page->press('Feltöltöm');
    $page->assertMissing('#description')->assertVisible('#description-file');

    $page->press('Elmondom');
    $page->assertMissing('#description-file')->assertSee('Elkezdem a felvételt');

    // The transcript arrives as after a recording: the field shows again, with the text.
    $page->script(<<<JS
        async () => {
            const wire = window.Livewire.find(document.querySelector('#description').closest('[wire\\\\:id]').getAttribute('wire:id'));
            await wire.set('dictations', { description: {$transcript->id} });
            await wire.pollDictations();
        }
    JS);
    $page->assertVisible('#description')
        ->assertValue('#description', 'A pénzügy zárja a hónapot, a vezető jóváhagyja.')
        ->assertNoJavaScriptErrors();
    expect($process->refresh()->description)->toBe('A pénzügy zárja a hónapot, a vezető jóváhagyja.');
});
