<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\ImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * "Belépés a nevében" (14 · 14.6). The form opens the customer side in a new tab.
 */
class ImpersonationController extends Controller
{
    public function store(Request $request, User $user, ImpersonationService $impersonation): RedirectResponse
    {
        Gate::authorize('impersonate', $user);

        $impersonation->start($request->user(), $user);

        return redirect()->route('client.landing');
    }
}
