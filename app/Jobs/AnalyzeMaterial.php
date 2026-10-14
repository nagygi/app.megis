<?php

namespace App\Jobs;

use App\Enums\MaterialStatus;
use App\Models\Material;
use App\Models\Organization;
use App\Services\Engine\ValueMeasurementEngine;
use App\Services\Materials\MaterialPipeline;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Runs the three steps of the value measurement on a material (06 · 6.4).
 */
class AnalyzeMaterial extends AiTaskJob
{
    public function __construct(public Material $material)
    {
        parent::__construct();
    }

    public function handle(ValueMeasurementEngine $engine, MaterialPipeline $pipeline): void
    {
        $this->material->update(['status' => MaterialStatus::Analyzing, 'error' => null]);

        $engine->analyze(
            $this->material,
            $pipeline->catalogFor($this->material),
            $pipeline->contextFor($this->material),
            $pipeline->speakersToScore($this->material),
        );

        $this->material->update(['status' => MaterialStatus::Processed, 'processed_at' => now()]);

        $pipeline->analyzed($this->material);
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
