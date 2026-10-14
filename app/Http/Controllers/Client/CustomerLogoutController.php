<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Impersonation;
use App\Services\Auth\GuardSession;
use App\Services\Auth\ImpersonationService;
use Illuminate\Http\RedirectResponse;

class CustomerLogoutController extends Controller
{
    public function __invoke(ImpersonationService $impersonation, GuardSession $guardSession): RedirectResponse
    {
        if ($impersonation->isImpersonating()) {
            $impersonation->end(Impersonation::END_EXIT);

            return redirect()->route('admin.dashboard');
        }

        $guardSession->logout(GuardSession::WEB);

        return redirect()->route('client.login');
    }
}
