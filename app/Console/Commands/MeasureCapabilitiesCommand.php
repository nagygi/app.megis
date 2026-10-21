<?php

namespace App\Console\Commands;

use App\Models\CapabilityFinding;
use App\Models\Material;
use App\Services\Capabilities\CapabilityAssessmentService;
use App\Services\Capabilities\CapabilityDetectionEngine;
use App\Services\Materials\MaterialPipeline;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Runs the capability measurement of one material here and now, for the
 * calibration on the Tesztpad (NM-12). With --dry nothing stays: the traces
 * and findings are printed, then everything, the AI call log too, is rolled back.
 */
#[Signature('megis:capabilities:run {material : Az anyag azonosítója vagy uuid-ja} {--dry : Csak kiírja az eredményt, nem menti}')]
#[Description('Képességmérés egy anyagon, kiírással (NM-12, Tesztpad-kalibráció)')]
class MeasureCapabilitiesCommand extends Command
{
    public function handle(CapabilityDetectionEngine $engine, MaterialPipeline $pipeline, CapabilityAssessmentService $assessments): int
    {
        $key = (string) $this->argument('material');
        $material = Material::query()->where(is_numeric($key) ? 'id' : 'uuid', $key)->first();

        if ($material === null || $material->organization_id === null) {
            $this->error(__('console.capabilities_run.not_found', ['material' => $key]));

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry');

        DB::beginTransaction();

        try {
            if (! $engine->measure($material, $pipeline->contextFor($material))) {
                $this->warn(__('console.capabilities_run.empty_library'));
            }

            $assessments->refreshForMaterial($material);
            $this->report($material);

            $dry ? DB::rollBack() : DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }

        $this->info($dry ? __('console.capabilities_run.rolled_back') : __('console.capabilities_run.saved'));

        return self::SUCCESS;
    }

    private function report(Material $material): void
    {
        $traces = $material->capabilityTraces()->get();
        $this->line(__('console.capabilities_run.traces', ['count' => $traces->count()]));

        $this->table(
            ['#', __('console.capabilities_run.columns.level'), __('console.capabilities_run.columns.direction'), __('console.capabilities_run.columns.quote')],
            $traces->map(fn ($trace) => [$trace->id, $trace->subject_level->label(), $trace->direction->label(), mb_strimwidth($trace->quote, 0, 90, '…')])->all(),
        );

        $findings = CapabilityFinding::query()->whereBelongsTo($material)->with(['capability', 'pattern', 'traces'])->get();
        $this->line(__('console.capabilities_run.findings', ['count' => $findings->count()]));

        $this->table(
            [__('console.capabilities_run.columns.capability'), __('console.capabilities_run.columns.pattern'), __('console.capabilities_run.columns.level'), __('console.capabilities_run.columns.confidence'), __('console.capabilities_run.columns.traces')],
            $findings->map(fn (CapabilityFinding $finding) => [
                $finding->capability->code,
                $finding->pattern?->code ?? __('console.capabilities_run.interpretation'),
                $finding->subject_level->label(),
                $finding->machine_confidence->label(),
                $finding->traces->pluck('id')->implode(', '),
            ])->all(),
        );
    }
}
