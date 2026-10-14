<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Auth\LandingResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sends a logged-in customer to the page the system chooses (14 · 14.4).
 */
class LandingController extends Controller
{
    public function __invoke(Request $request, LandingResolver $landing): RedirectResponse
    {
        return redirect($landing->urlFor($request->user()));
    }
}
