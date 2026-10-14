<?php

namespace App\Services\Report;

use App\Enums\MainDiagnosisStage;
use App\Enums\MainDiagnosisSubject;
use App\Models\MainDiagnosis;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\ReportSnapshot;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The published report: the latest frozen snapshot, who may read it
 * (27 · 27.1), and the "Riportok" of the menu (27 · 27.12).
 */
class ReportService
{
    /**
     * The URL slug of each chapter (27).
     */
    public const SLUGS = ['values' => 'ertekek', 'processes' => 'folyamatok', 'areas' => 'teruletek', 'brand' => 'markad-alapja'];

    public function snapshot(MainDiagnosis $main): ?ReportSnapshot
    {
        return ReportSnapshot::query()->where('main_diagnosis_id', $main->id)->latest('sequence')->first();
    }

    /**
     * The leader and the organization's leaders; a person inside a company
     * only themselves; never a peer, who gets the Útravaló (27 · 27.1, 29).
     */
    public function canView(User $user, MainDiagnosis $main): bool
    {
        if ($main->stage !== MainDiagnosisStage::Published) {
            return false;
        }

        if ($main->leader_id === $user->id) {
            return true;
        }

        if ($main->subject === MainDiagnosisSubject::Person) {
            return false;
        }

        return Membership::query()
            ->where('organization_id', $main->diagnosis->organization_id)
            ->where('user_id', $user->id)
            ->where('is_organization_leader', true)
            ->exists();
    }

    /**
     * "Teljes · 04.18. · Belső 38%" (27 · 27.12).
     *
     * @return Collection<int, MainDiagnosis>
     */
    public function reportsFor(User $user, Organization $organization): Collection
    {
        return MainDiagnosis::query()
            ->where('stage', MainDiagnosisStage::Published)
            ->whereHas('diagnosis', fn ($query) => $query->where('organization_id', $organization->id))
            ->with('diagnosis.team')
            ->latest('published_at')
            ->get()
            ->filter(fn (MainDiagnosis $main) => $this->canView($user, $main))
            ->values();
    }

    public static function chapterFromSlug(string $slug): ?string
    {
        $chapter = array_search($slug, self::SLUGS, true);

        return $chapter === false ? null : $chapter;
    }
}
