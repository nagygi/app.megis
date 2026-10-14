<?php

namespace App\Console\Commands;

use App\Services\Ai\AiGateway;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * The 12.7 proof of concept: the same Hungarian meeting recording through
 * several providers, side by side (speed, speakers, words, cost). The
 * transcripts are saved for reading; the recording may hold personal data,
 * so it never goes into the repository.
 */
#[Signature('megis:transcription:compare {file : A hangfájl útvonala} {--model=* : szolgáltató:modell, pl. openai:gpt-4o-transcribe-diarize}')]
#[Description('Átíró szolgáltatók összevetése egy felvételen (12 · 12.7)')]
class CompareTranscriptionCommand extends Command
{
    public function handle(AiGateway $ai): int
    {
        $file = (string) $this->argument('file');
        $models = (array) $this->option('model');

        if (! is_file($file) || $models === []) {
            $this->error(__('console.transcription_compare.usage'));

            return self::FAILURE;
        }

        $directory = 'transcription-poc/'.now()->format('Ymd-His');
        $rows = [];

        foreach ($models as $option) {
            [$provider, $model] = array_pad(explode(':', (string) $option, 2), 2, '');

            if ($provider === '' || $model === '') {
                $this->warn(__('console.transcription_compare.invalid_model', ['option' => $option]));

                continue;
            }

            $startedAt = microtime(true);

            try {
                ['response' => $response, 'call' => $call] = $ai->transcribeFile($file, $provider, $model);
            } catch (Throwable $exception) {
                $rows[] = [$option, '–', '–', '–', '–', '–', class_basename($exception).': '.mb_substr($exception->getMessage(), 0, 80)];

                continue;
            }

            $path = $directory.'/'.preg_replace('/[^a-z0-9._-]+/i', '-', $option).'.txt';
            Storage::disk('local')->put($path, $response->segments->isEmpty()
                ? $response->text
                : $response->segments->map(fn ($segment) => $segment->speaker.': '.trim($segment->text))->implode("\n"));

            $rows[] = [
                $option,
                number_format(microtime(true) - $startedAt, 1, ',', ''),
                $response->segments->pluck('speaker')->unique()->count(),
                str_word_count($response->text, 0, 'áéíóöőúüűÁÉÍÓÖŐÚÜŰ'),
                $call->audio_minutes === null ? '–' : number_format($call->audio_minutes, 1, ',', ''),
                $call->cost_usd === null ? '–' : '$'.number_format($call->cost_usd, 4, ',', ''),
                storage_path('app/private/'.$path),
            ];
        }

        $this->table(__('console.transcription_compare.columns'), $rows);

        return self::SUCCESS;
    }
}
