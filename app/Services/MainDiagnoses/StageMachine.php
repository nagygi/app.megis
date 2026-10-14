<?php

namespace App\Services\MainDiagnoses;

use App\Enums\DiagnosisStatus;
use App\Enums\MainDiagnosisStage;
use App\Events\MainDiagnosisStageChanged;
use App\Exceptions\DiagnosisException;
use App\Models\Diagnosis;
use App\Models\MainDiagnosis;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Moves a main diagnosis between its stages (17 · 17.5). The stage sets the
 * 04 status of the diagnosis and of its subdiagnoses, which follow the parent
 * and cannot move on their own (17 · 17.4). Every change is logged.
 */
class StageMachine
{
    private const TIMESTAMPS = [
        'invited' => 'invited_at',
        'running' => 'running_at',
        'reading' => 'reading_at',
        'published' => 'published_at',
        'closed' => 'closed_at',
    ];

    public function transition(MainDiagnosis $main, MainDiagnosisStage $to, ?User $actor = null, ?string $reason = null): void
    {
        $from = $main->stage;

        if (! $from->canTransitionTo($to)) {
            throw new DiagnosisException(__('admin.main_diagnoses.stage_not_allowed', ['from' => $from->label(), 'to' => $to->label()]));
        }

        DB::transaction(function () use ($main, $from, $to, $actor, $reason): void {
            $diagnosis = $main->diagnosis;
            $fromStatus = $diagnosis->status;
            // Withdrawn after publishing: back in reading with the Draft status (21 · 21.5, plan 3.6).
            $toStatus = $from === MainDiagnosisStage::Published && $to === MainDiagnosisStage::Reading
                ? DiagnosisStatus::Draft
                : ($to->status() ?? $fromStatus);

            $main->stage = $to;

            if (isset(self::TIMESTAMPS[$to->value])) {
                $main->{self::TIMESTAMPS[$to->value]} = now();
            }

            $main->save();

            if ($toStatus !== $fromStatus) {
                $diagnosis->update(['status' => $toStatus]);
                Diagnosis::query()->where('parent_id', $diagnosis->id)->update(['status' => $toStatus]);
            }

            $diagnosis->transitions()->create([
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'from_stage' => $from,
                'to_stage' => $to,
                'created_by' => $actor?->id ?? auth()->id(),
                'reason' => $reason,
            ]);

            MainDiagnosisStageChanged::dispatch($main, $from, $to, $actor);
        });
    }
}
