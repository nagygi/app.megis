<?php

namespace App\Services\MainDiagnoses;

use App\Enums\DiagnosisStep;
use App\Enums\MainDiagnosisSubject;
use App\Enums\StepMark;
use App\Models\Diagnosis;
use App\Models\DiagnosisType;
use App\Models\MainDiagnosis;
use App\Models\Membership;
use App\Models\OrganizationValueSet;
use App\Models\PersonalValueSet;
use App\Models\User;
use App\Models\Version;

/**
 * Which steps are done (23 · 23.3). A step is done when its own ticket says
 * it is complete; only "viewed" (read-only steps) and "skipped" (processes)
 * are marks the user leaves (plan 5).
 */
class StepProgress
{
    public function forLeader(MainDiagnosis $main, User $user): StepList
    {
        $marks = $this->marks($main, $user);

        return new StepList(array_map(function (DiagnosisStep $step) use ($main, $user, $marks): StepState {
            $readonly = $this->isReadonly($main, $user, $step);

            return new StepState(
                step: $step,
                done: $this->isDone($main, $user, $step, $readonly, $marks),
                readonly: $readonly,
                minutes: $readonly ? (int) config('diagnosis.readonly_step_minutes') : $step->minutes(),
            );
        }, $main->leaderSteps()));
    }

    /**
     * T2: own values and experience now; adding situations while it runs (25 · 25.3).
     */
    public function forPeer(MainDiagnosis $main, User $user): StepList
    {
        return new StepList(array_map(fn (DiagnosisStep $step) => new StepState(
            step: $step,
            done: $this->isDone($main, $user, $step, false, []),
            readonly: false,
            minutes: $step->minutes(),
        ), $main->peerSteps()));
    }

    /**
     * "A szervezet értékei" and "A márkád alapja" are read-only for a leader
     * who is not the organization's leader (17 · 17.3), and inside the
     * diagnosis once it runs, because the version is recorded then (plan 15 · 1).
     */
    public function isReadonly(MainDiagnosis $main, User $user, DiagnosisStep $step): bool
    {
        if (! in_array($step, [DiagnosisStep::OrganizationValues, DiagnosisStep::Brand], true)) {
            return false;
        }

        if (! $main->stage->isBeforeRunning()) {
            return true;
        }

        return $main->subject !== MainDiagnosisSubject::Individual
            && ! Membership::query()
                ->where('organization_id', $main->diagnosis->organization_id)
                ->where('user_id', $user->id)
                ->where('is_organization_leader', true)
                ->exists();
    }

    /**
     * The value set version the experience has to answer: the recorded one
     * once the diagnosis runs, the latest before (08 · 8.4).
     */
    public function expectedValueSetVersionId(MainDiagnosis $main): ?int
    {
        if ($main->value_set_version_id !== null) {
            return $main->value_set_version_id;
        }

        return OrganizationValueSet::query()
            ->where('organization_id', $main->diagnosis->organization_id)
            ->first()
            ?->latestVersion()
            ->value('id');
    }

    /**
     * The user's Megélés subdiagnosis of this main diagnosis (08 · 8.3, 25 · 25.4).
     */
    public function experienceDiagnosis(MainDiagnosis $main, User $user): ?Diagnosis
    {
        return Diagnosis::query()
            ->where('parent_id', $main->diagnosis_id)
            ->where('user_id', $user->id)
            ->whereNull('excluded_at')
            ->where('diagnosis_type_id', DiagnosisType::byKey(DiagnosisType::EXPERIENCE)->id)
            ->latest('id')
            ->first();
    }

    /**
     * The own values are confirmed by leaving the step, which records a version (24).
     */
    public function ownValuesComplete(?PersonalValueSet $set, bool $individual): bool
    {
        $values = $set?->latestVersion?->snapshot['values'] ?? null;

        if ($values === null) {
            return false;
        }

        $min = (int) config($individual ? 'diagnosis.own_values.individual_min' : 'diagnosis.own_values.min');

        if (count($values) < $min || count($values) > (int) config('diagnosis.own_values.max')) {
            return false;
        }

        foreach ($values as $value) {
            if (blank($value['name'] ?? null) || blank($value['meaning'] ?? null)) {
                return false;
            }

            // A private person's own values are the organization's values, with both ends (24, question 2).
            if ($individual && (blank($value['low_example'] ?? null) || blank($value['high_example'] ?? null))) {
                return false;
            }
        }

        return true;
    }

    /**
     * 3–5 values, each with both ends of the scale (08 · 8.1).
     */
    public function organizationValuesComplete(?Version $version): bool
    {
        $values = $version?->snapshot['values'] ?? [];

        if (count($values) < OrganizationValueSet::MIN_VALUES || count($values) > OrganizationValueSet::MAX_VALUES) {
            return false;
        }

        foreach ($values as $value) {
            if (blank($value['low_example'] ?? null) || blank($value['high_example'] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * All four elements: the values as the root, and the three texts (08 · 8.2).
     */
    public function brandComplete(?Version $version): bool
    {
        if ($version === null || count($version->snapshot['values'] ?? []) < OrganizationValueSet::MIN_VALUES) {
            return false;
        }

        foreach (OrganizationValueSet::BRAND_FIELDS as $field) {
            if (blank($version->snapshot[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, StepMark>  $marks
     */
    private function isDone(MainDiagnosis $main, User $user, DiagnosisStep $step, bool $readonly, array $marks): bool
    {
        if ($readonly && in_array($step, [DiagnosisStep::OrganizationValues, DiagnosisStep::Brand], true)) {
            return ! $main->stage->isBeforeRunning() || ($marks[$step->value] ?? null) === StepMark::Viewed;
        }

        return match ($step) {
            DiagnosisStep::OwnValues => $this->ownValuesComplete(
                PersonalValueSet::query()->with('latestVersion')->where('user_id', $user->id)->first(),
                $main->subject === MainDiagnosisSubject::Individual,
            ),
            DiagnosisStep::OrganizationValues => $this->organizationValuesComplete($this->latestOrganizationVersion($main)),
            DiagnosisStep::Brand => $this->brandComplete($this->latestOrganizationVersion($main)),
            DiagnosisStep::Experience => ($diagnosis = $this->experienceDiagnosis($main, $user)) !== null
                && $diagnosis->value_set_version_id === $this->expectedValueSetVersionId($main),
            DiagnosisStep::Processes => isset($marks[$step->value]),
            DiagnosisStep::Visibility => ! $main->stage->isBeforeRunning(),
        };
    }

    private function latestOrganizationVersion(MainDiagnosis $main): ?Version
    {
        return OrganizationValueSet::query()
            ->where('organization_id', $main->diagnosis->organization_id)
            ->first()
            ?->latestVersion;
    }

    /**
     * @return array<string, StepMark>
     */
    private function marks(MainDiagnosis $main, User $user): array
    {
        return $main->stepMarks()
            ->where('user_id', $user->id)
            ->get()
            ->mapWithKeys(fn ($mark) => [$mark->step->value => $mark->mark])
            ->all();
    }
}
