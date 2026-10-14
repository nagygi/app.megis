<?php

namespace App\Services\Materials;

use Illuminate\Support\Facades\Process;

/**
 * Length of an audio file with ffprobe (plan 3.3). Browser recordings (webm)
 * carry no duration, so the file itself is measured.
 */
class AudioInspector
{
    public function isAvailable(): bool
    {
        return Process::timeout(10)->run([(string) config('megis.ffprobe_path'), '-version'])->successful();
    }

    /**
     * Seconds, or null when ffprobe is missing or cannot read the file.
     */
    public function durationSeconds(string $localPath): ?int
    {
        $result = Process::timeout(60)->run([
            (string) config('megis.ffprobe_path'),
            '-v', 'error',
            '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1',
            $localPath,
        ]);

        if (! $result->successful() || ! is_numeric(trim($result->output()))) {
            return null;
        }

        return (int) ceil((float) trim($result->output()));
    }
}
