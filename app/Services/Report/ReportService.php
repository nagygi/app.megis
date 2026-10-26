<?php

namespace App\Services\Report;

use App\Enums\DiagnosisLevel;
use App\Enums\DiagnosisStatus;
use App\Enums\MainDiagnosisStage;
use App\Enums\MainDiagnosisSubject;
use App\Enums\MembershipStatus;
use App\Models\Diagnosis;
use App\Models\MainDiagnosis;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\ReportSnapshot;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The published report: the latest frozen snapshot, who may read it
 * (27 · 27.1), and the "Riportok" of the menu (27 · 27.12); the same for the
 * report of a standalone diagnosis (04 · 4.6).
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

    /**
     * A published standalone diagnosis (04 · 4.6): a user's only to them; a
     * team's to its members and the organization's leaders; an
     * organization's or a process' to every active member.
     */
    public function canViewStandalone(User $user, Diagnosis $diagnosis): bool
    {
        return $this->standaloneQuery($user, $diagnosis->organization)->whereKey($diagnosis->id)->exists();
    }

    /**
     * The standalone reports of the menu, the latest first (04 · 4.6).
     *
     * @return Collection<int, Diagnosis>
     */
    public function standaloneReportsFor(User $user, Organization $organization): Collection
    {
        return $this->standaloneQuery($user, $organization)->orderByDesc('diagnosed_on')->orderByDesc('id')->get();
    }

    /**
     * Who will read the standalone report: the admin's preview names them.
     *
     * @return Collection<int, User>
     */
    public function standaloneViewers(Diagnosis $diagnosis): Collection
    {
        $memberships = Membership::query()
            ->whereBelongsTo($diagnosis->organization)
            ->where('status', MembershipStatus::Active)
            ->with('user')
            ->get();
        $teamUserIds = $diagnosis->level === DiagnosisLevel::Team
            ? DB::table('team_user')->where('team_id', $diagnosis->team_id)->pluck('user_id')
            : collect();

        $memberships = match ($diagnosis->level) {
            DiagnosisLevel::User => $memberships->where('user_id', $diagnosis->user_id),
            DiagnosisLevel::Team => $memberships->filter(fn (Membership $membership) => $membership->is_organization_leader || $teamUserIds->contains($membership->user_id)),
            default => $memberships,
        };

        return $memberships->pluck('user')->filter()->sortBy('name')->values();
    }

    /**
     * The organization's published standalone diagnoses the user may read.
     *
     * @return Builder<Diagnosis>
     */
    private function standaloneQuery(User $user, Organization $organization): Builder
    {
        $membership = Membership::query()
            ->whereBelongsTo($organization)
            ->where('user_id', $user->id)
            ->where('status', MembershipStatus::Active)
            ->first();

        return Diagnosis::query()
            ->whereBelongsTo($organization)
            ->where('status', DiagnosisStatus::Published)
            ->whereNull('parent_id')
            ->whereDoesntHave('main')
            ->when($membership === null, fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query->where('level', DiagnosisLevel::User)->where('user_id', $user->id))
                ->orWhere(fn (Builder $query) => $query->where('level', DiagnosisLevel::Team)->when(
                    ! $membership?->is_organization_leader,
                    fn (Builder $query) => $query->whereIn('team_id', DB::table('team_user')->where('user_id', $user->id)->select('team_id')),
                ))
                ->orWhereIn('level', [DiagnosisLevel::Organization, DiagnosisLevel::Process]));
    }

    public static function chapterFromSlug(string $slug): ?string
    {
        $chapter = array_search($slug, self::SLUGS, true);

        return $chapter === false ? null : $chapter;
    }
}
