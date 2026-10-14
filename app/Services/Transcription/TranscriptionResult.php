<?php

namespace App\Services\Transcription;

use App\Models\AiCall;

/**
 * A transcript as speaker turns: "Beszélő 1", "Beszélő 2", …
 */
final readonly class TranscriptionResult
{
    /**
     * @param  list<array{speaker: string, start: ?float, end: ?float, text: string}>  $segments
     */
    public function __construct(
        public string $text,
        public array $segments,
        public ?int $durationSeconds = null,
        public ?AiCall $call = null,
    ) {}
}
