<?php

namespace App\Services\Transcription;

use App\Ai\AiContext;

/**
 * Speech to text with speaker recognition (06, 12 · 12.6). The provider is
 * chosen by the 12.7 proof of concept; features only see this interface.
 */
interface Transcriber
{
    /**
     * @param  string  $path  on the materials disk
     */
    public function transcribe(string $path, AiContext $context): TranscriptionResult;
}
