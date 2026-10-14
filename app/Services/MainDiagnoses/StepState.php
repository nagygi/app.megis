<?php

namespace App\Services\MainDiagnoses;

use App\Enums\DiagnosisStep;

/**
 * One step on V1 / T2: done, read-only, and its estimated minutes (23 · 23.1).
 */
final readonly class StepState
{
    public function __construct(
        public DiagnosisStep $step,
        public bool $done,
        public bool $readonly,
        public int $minutes,
    ) {}
}
