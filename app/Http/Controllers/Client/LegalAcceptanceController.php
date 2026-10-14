<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Auth\ImpersonationService;
use App\Services\Auth\LandingResolver;
use App\Services\Legal\LegalDocuments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Változott az ÁSZF." (14 · 14.5): the customer accepts the new version or logs out.
 */
class LegalAcceptanceController extends Controller
{
    public function show(Request $request, LegalDocuments $legalDocuments, LandingResolver $landing, ImpersonationService $impersonation): View|RedirectResponse
    {
        $pending = $legalDocuments->pendingReacceptance($request->user());

        if ($pending->isEmpty()) {
            return redirect($landing->urlFor($request->user()));
        }

        return view('client.legal-acceptance', [
            'versions' => $pending,
            'isImpersonating' => $impersonation->isImpersonating(),
        ]);
    }

    public function store(Request $request, LegalDocuments $legalDocuments, LandingResolver $landing): RedirectResponse
    {
        $user = $request->user();

        $legalDocuments->recordAcceptance($user, $legalDocuments->pendingReacceptance($user));

        return redirect()->intended($landing->urlFor($user));
    }
}
