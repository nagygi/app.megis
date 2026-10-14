<?php

namespace App\Http\Controllers\Client;

use App\Enums\MainDiagnosisStage;
use App\Http\Controllers\Controller;
use App\Models\MainDiagnosis;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The organization's home (26 · 26.4): the newest main diagnosis the user
 * leads, otherwise the newest one they are a peer in; the page of that
 * diagnosis opens (V1, V10, T5). Without a diagnosis, a greeting.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request, Organization $organization): View|RedirectResponse
    {
        $user = $request->user();
        $diagnoses = $this->diagnoses($organization, $user);
        $home = $diagnoses->first(fn (MainDiagnosis $main) => $main->isLeader($user)) ?? $diagnoses->first();

        if ($home !== null) {
            return redirect()->route('client.diagnosis.show', [$organization, $home->diagnosis]);
        }

        return view('client.home', [
            'organization' => $organization,
            'user' => $user,
            'diagnoses' => $diagnoses,
        ]);
    }

    /**
     * @return Collection<int, MainDiagnosis>
     */
    private function diagnoses(Organization $organization, User $user): Collection
    {
        return MainDiagnosis::query()
            ->withParticipant($user)
            ->whereNotIn('stage', [MainDiagnosisStage::Draft->value, MainDiagnosisStage::Closed->value])
            ->whereHas('diagnosis', fn ($query) => $query->where('organization_id', $organization->id))
            ->with('diagnosis.team', 'diagnosis.organization', 'diagnosis.user')
            ->latest('id')
            ->get()
                // Peers are invited when the diagnosis starts running (25 · 25.1).
            ->filter(fn (MainDiagnosis $main) => $main->isLeader($user)
                || in_array($main->stage, [MainDiagnosisStage::Running, MainDiagnosisStage::Reading, MainDiagnosisStage::Published], true))
            ->values();
    }
}
