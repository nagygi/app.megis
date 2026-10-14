<?php

namespace App\Services\Transcription;

use App\Ai\AiContext;

/**
 * A fixed sample transcript for local work without an API key
 * (MEGIS_TRANSCRIBER=fake). Never used in production.
 */
class FakeTranscriber implements Transcriber
{
    public function transcribe(string $path, AiContext $context): TranscriptionResult
    {
        $segments = [
            ['speaker' => 'Beszélő 1', 'start' => 0.0, 'end' => 14.0, 'text' => 'A múlt héten csúszott a szállítás, és inkább azonnal szóltam az ügyfélnek, mint csütörtökön.'],
            ['speaker' => 'Beszélő 2', 'start' => 14.0, 'end' => 25.0, 'text' => 'Én azt mondtam, hogy ha én írtam alá, akkor én állom a következményét.'],
            ['speaker' => 'Beszélő 1', 'start' => 25.0, 'end' => 34.0, 'text' => 'A végén azért nevettünk egyet, mert a helyzet tényleg abszurd volt.'],
        ];

        return new TranscriptionResult(
            text: implode("\n", array_column($segments, 'text')),
            segments: $segments,
            durationSeconds: 34,
        );
    }
}
