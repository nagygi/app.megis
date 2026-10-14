<?php

namespace App\Jobs;

use App\Models\MainDiagnosis;
use App\Services\Diagnoses\DiagnosisCalculator;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Recalculates a main diagnosis after a processed material, an experience,
 * a withdrawn consent or a changed leader (plan 3.6). One per diagnosis
 * waits in the queue at a time, a little delayed so a burst of events is
 * counted once; an event while it runs queues the next one.
 */
class RecalculateMainDiagnosis implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 600;

    public function __construct(public MainDiagnosis $main)
    {
        $this->afterCommit();
        $this->delay(now()->addSeconds((int) config('diagnosis.recalculation_delay_seconds')));
    }

    public function uniqueId(): string
    {
        return (string) $this->main->id;
    }

    public function handle(DiagnosisCalculator $calculator): void
    {
        $calculator->recalculate($this->main->fresh());
    }
}
