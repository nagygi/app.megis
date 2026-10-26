<?php

namespace App\Http\Controllers\Client;

use App\Enums\MainDiagnosisStage;
use App\Http\Controllers\Controller;
use App\Models\MainDiagnosis;
use App\Models\Organization;
use App\Models\User;
use App\Services\Auth\LandingResolver;
use App\Services\MainDiagnoses\HomePreview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The organization's home (26 · 26.4): the diagnosis the login opens too
 * (V1, V10, V12, T1, T5). Before one opens for the user, a greeting with the
 * way ahead: the leader's steps and the team's, or the peer's.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request, Organization $organization, LandingResolver $landing, HomePreview $preview): View|RedirectResponse
    {
        $user = $request->user();

        if ($home = $landing->diagnosisFor($user, $organization)) {
            return redirect()->route('client.diagnosis.show', [$organization, $home->diagnosis]);
        }

        return view('client.home', [
            'organization' => $organization,
            'user' => $user,
            'lead' => $this->lead($organization, $user),
            'preview' => $preview->for($organization, $user),
        ]);
    }

    /**
     * "amint elindul" while the user waits for a diagnosis (a peer is invited
     * when it runs, 25 · 25.1) or none of the organization's has started yet;
     * otherwise the user takes part in none of the started ones, e.g. an
     * organization leader while someone else leads it.
     */
    private function lead(Organization $organization, User $user): string
    {
        $inOrganization = fn ($query) => $query->where('organization_id', $organization->id);

        $waiting = MainDiagnosis::query()
            ->withParticipant($user)
            ->inStages(MainDiagnosisStage::Draft, MainDiagnosisStage::Invited, MainDiagnosisStage::Filling)
            ->whereHas('diagnosis', $inOrganization)
            ->exists();

        $started = MainDiagnosis::query()
            ->where('stage', '!=', MainDiagnosisStage::Draft->value)
            ->whereHas('diagnosis', $inOrganization)
            ->exists();

        return $waiting || ! $started
            ? __('client.home.lead', ['organization' => $organization->name])
            : __('client.home.lead_not_participant');
    }
}
