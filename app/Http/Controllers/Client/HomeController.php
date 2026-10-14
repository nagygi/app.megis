<?php

namespace App\Http\Controllers\Client;

use App\Enums\MainDiagnosisStage;
use App\Http\Controllers\Controller;
use App\Models\MainDiagnosis;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The organization's home: the diagnoses the user takes part in. The
 * diagnosis homes (V10, T5, 26) open from here and from the login (14 · 14.4).
 */
class HomeController extends Controller
{
    public function __invoke(Request $request, Organization $organization): View
    {
        $user = $request->user();

        return view('client.home', [
            'organization' => $organization,
            'user' => $user,
            'diagnoses' => MainDiagnosis::query()
                ->withParticipant($user)
                ->whereNotIn('stage', [MainDiagnosisStage::Draft->value, MainDiagnosisStage::Closed->value])
                ->whereHas('diagnosis', fn ($query) => $query->where('organization_id', $organization->id))
                ->with('diagnosis.team', 'diagnosis.organization', 'diagnosis.user')
                ->latest('id')
                ->get()
                // Peers are invited when the diagnosis starts running (25 · 25.1).
                ->filter(fn (MainDiagnosis $main) => $main->isLeader($user)
                    || in_array($main->stage, [MainDiagnosisStage::Running, MainDiagnosisStage::Reading, MainDiagnosisStage::Published], true))
                ->values(),
        ]);
    }
}
