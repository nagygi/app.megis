<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Impersonation;
use App\Services\Auth\ImpersonationService;
use Illuminate\Http\RedirectResponse;

/**
 * "Kilépés" in the coral bar ends the work in the customer's name (14 · 14.6).
 */
class ImpersonationController extends Controller
{
    public function destroy(ImpersonationService $impersonation): RedirectResponse
    {
        $current = $impersonation->current();
        $impersonation->end(Impersonation::END_EXIT);

        return $current === null
            ? redirect()->route('admin.dashboard')
            : redirect()->route('admin.clients.show', $current->user_id)
                ->with('status', __('impersonation.ended', ['name' => $current->user->name]));
    }
}
