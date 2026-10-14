<?php

namespace App\Jobs;

use App\Enums\MaterialStatus;
use App\Models\Material;
use App\Models\Organization;
use App\Services\Materials\MaterialPipeline;
use App\Services\Transcription\Transcriber;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Speech to text with speaker recognition for an audio material (06, 12 · 12.6).
 */
class TranscribeMaterial extends AiTaskJob
{
    public function __construct(public Material $material)
    {
        parent::__construct();
    }

    public function handle(Transcriber $transcriber, MaterialPipeline $pipeline): void
    {
        $this->material->update(['status' => MaterialStatus::Transcribing, 'error' => null]);

        $result = $transcriber->transcribe((string) $this->material->audio_path, $pipeline->contextFor($this->material));

        if (trim($result->text) === '') {
            $this->fail(new \RuntimeException('The transcript is empty.'));

            return;
        }

        $this->material->update([
            'text' => $result->text,
            'segments' => $result->segments,
            'audio_seconds' => $this->material->audio_seconds ?? $result->durationSeconds,
            'transcription_ai_call_id' => $result->call?->id,
        ]);

        $pipeline->transcribed($this->material);
    }

    protected function markFailed(?Throwable $exception): void
    {
        app(MaterialPipeline::class)->failed($this->material, $exception);
    }

    protected function failureSummary(): string
    {
        return __('materials.failure_summary', ['name' => $this->material->original_name ?? $this->material->context->label()]);
    }

    protected function failureOrganization(): ?Organization
    {
        return $this->material->organization;
    }

    protected function failureSubject(): ?Model
    {
        return $this->material;
    }
}
