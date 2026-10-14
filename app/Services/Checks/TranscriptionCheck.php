<?php

namespace App\Services\Checks;

use App\Services\Materials\AudioInspector;

/**
 * Audio can be processed (06, 12 · 12.7): a real transcriber in production,
 * a chosen model, and ffprobe to measure recordings (plan 3.3).
 */
class TranscriptionCheck implements Check
{
    public function __construct(private AudioInspector $inspector) {}

    public function run(): CheckResult
    {
        $label = __('checks.transcription.label');

        if (config('megis.transcriber') === 'fake' && app()->isProduction()) {
            return CheckResult::error($label, __('checks.transcription.fake_in_production'));
        }

        $problems = [];

        if (config('megis.transcriber') !== 'fake' && blank(config('ai.tasks.transcription.model'))) {
            $problems[] = __('checks.transcription.no_model');
        }

        if (! $this->inspector->isAvailable()) {
            $problems[] = __('checks.transcription.no_ffprobe');
        }

        return $problems === []
            ? CheckResult::ok($label, __('checks.transcription.ok'))
            : CheckResult::warning($label, implode(' ', $problems));
    }
}
