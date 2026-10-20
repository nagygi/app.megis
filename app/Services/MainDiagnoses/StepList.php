<?php

namespace App\Services\MainDiagnoses;

use App\Enums\DiagnosisStep;

/**
 * The steps of one participant in order, with the counts V1·b shows
 * („{k} / {n} kész · kb. {perc} perc van hátra”, 23 · 23.2).
 */
final readonly class StepList
{
    /**
     * @param  list<StepState>  $steps
     */
    public function __construct(public array $steps) {}

    public function count(): int
    {
        return count($this->steps);
    }

    public function doneCount(): int
    {
        return count(array_filter($this->steps, fn (StepState $state) => $state->done));
    }

    public function totalMinutes(): int
    {
        return array_sum(array_map(fn (StepState $state) => $state->minutes, $this->steps));
    }

    public function remainingMinutes(): int
    {
        return array_sum(array_map(fn (StepState $state) => $state->done ? 0 : $state->minutes, $this->steps));
    }

    /**
     * The coral step: the first one not done yet.
     */
    public function current(): ?StepState
    {
        foreach ($this->steps as $state) {
            if (! $state->done) {
                return $state;
            }
        }

        return null;
    }

    public function firstMissing(?DiagnosisStep $except = null): ?StepState
    {
        foreach ($this->steps as $state) {
            if (! $state->done && $state->step !== $except) {
                return $state;
            }
        }

        return null;
    }

    public function get(DiagnosisStep $step): ?StepState
    {
        foreach ($this->steps as $state) {
            if ($state->step === $step) {
                return $state;
            }
        }

        return null;
    }

    public function has(DiagnosisStep $step): bool
    {
        return $this->get($step) !== null;
    }

    /**
     * The step after the given one, for "Tovább" (23 · 23.3).
     */
    public function after(DiagnosisStep $step): ?StepState
    {
        $found = false;

        foreach ($this->steps as $state) {
            if ($found) {
                return $state;
            }

            $found = $state->step === $step;
        }

        return null;
    }

    public function before(DiagnosisStep $step): ?StepState
    {
        $previous = null;

        foreach ($this->steps as $state) {
            if ($state->step === $step) {
                return $previous;
            }

            $previous = $state;
        }

        return null;
    }

    /**
     * "3 / 6" in the header (23 · 23.3).
     */
    public function position(DiagnosisStep $step): int
    {
        foreach ($this->steps as $index => $state) {
            if ($state->step === $step) {
                return $index + 1;
            }
        }

        return 0;
    }

    public function isStarted(): bool
    {
        return $this->doneCount() > 0;
    }

    /**
     * Whether the participant has done a step themselves. The organization
     * values and the brand may come filled in by the admin, so on their own
     * they do not turn V1 into V1·b "Innen folytatod" (23 · 23.2).
     */
    public function hasOwnProgress(): bool
    {
        foreach ($this->steps as $state) {
            if ($state->done && ! in_array($state->step, [DiagnosisStep::OrganizationValues, DiagnosisStep::Brand], true)) {
                return true;
            }
        }

        return false;
    }
}
