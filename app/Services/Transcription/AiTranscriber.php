<?php

namespace App\Services\Transcription;

use App\Ai\AiContext;
use App\Models\Material;
use App\Services\Ai\AiGateway;

/**
 * Transcription through the AI layer with the provider and model of
 * config('ai.tasks.transcription'); every call is logged with its minutes (16 · 16.4).
 */
class AiTranscriber implements Transcriber
{
    public function __construct(private AiGateway $gateway) {}

    public function transcribe(string $path, AiContext $context): TranscriptionResult
    {
        $response = $this->gateway->transcribe(Material::DISK, $path, $context);
        $speakers = [];

        $segments = $response['response']->segments->map(function ($segment) use (&$speakers): array {
            // Providers name speakers differently ("A", "speaker_0"); we number them in order.
            $speakers[$segment->speaker] ??= __('materials.speaker', ['number' => count($speakers) + 1]);

            return [
                'speaker' => $speakers[$segment->speaker],
                'start' => $segment->startSeconds,
                'end' => $segment->endSeconds,
                'text' => trim($segment->text),
            ];
        })->values()->all();

        if ($segments === []) {
            $segments = [['speaker' => __('materials.speaker', ['number' => 1]), 'start' => null, 'end' => null, 'text' => trim($response['response']->text)]];
        }

        return new TranscriptionResult(
            text: $response['response']->text,
            segments: $segments,
            durationSeconds: $response['response']->usage->audioSeconds === null ? null : (int) ceil($response['response']->usage->audioSeconds),
            call: $response['call'],
        );
    }
}
