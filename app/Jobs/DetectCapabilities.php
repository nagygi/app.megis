<?php

namespace App\Jobs;

use App\Enums\CapabilityMeasurementStatus;
use App\Models\Material;
use App\Models\Organization;
use App\Services\Capabilities\CapabilityAssessmentService;
use App\Services\Capabilities\CapabilityDetectionEngine;
use App\Services\Capabilities\CapabilityMeasurementPolicy;
use App\Services\Materials\MaterialPipeline;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Runs the capability measurement on a material after its value measurement
 * (NM-12). A separate job: its failure never touches the values, and "Újra"
 * in the Beérkező starts only this one again.
 */
class DetectCapabilities extends AiTaskJob
{
    public function __construct(public Material $material, public bool $force = false)
    {
        parent::__construct();
    }

    public function handle(CapabilityDetectionEngine $engine, MaterialPipeline $pipeline, CapabilityMeasurementPolicy $policy, CapabilityAssessmentService $assessments): void
    {
        if (! $this->force && $this->material->capability_status === CapabilityMeasurementStatus::Processed) {
            return;
        }

        if (! $policy->appliesTo($this->material)) {
            $this->material->update(['capability_status' => CapabilityMeasurementStatus::Skipped]);

            return;
        }

        $this->material->update(['capability_status' => CapabilityMeasurementStatus::Analyzing, 'capability_error' => null]);

        $measured = $engine->measure($this->material, $pipeline->contextFor($this->material));

        $this->material->update([
            'capability_status' => $measured ? CapabilityMeasurementStatus::Processed : CapabilityMeasurementStatus::Skipped,
            'capability_processed_at' => now(),
        ]);

        $assessments->refreshForMaterial($this->material);
    }

    protected function failureKind(): ?string
    {
        return 'capability_detection';
    }

    protected function markFailed(?Throwable $exception): void
    {
        $this->material->update([
            'capability_status' => CapabilityMeasurementStatus::Failed,
            'capability_error' => $exception === null ? null : mb_substr(class_basename($exception).': '.$exception->getMessage(), 0, 1000),
        ]);
    }

    protected function failureSummary(): string
    {
        return __('capabilities.failure_summary', ['name' => $this->material->original_name ?? $this->material->context->label()]);
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
