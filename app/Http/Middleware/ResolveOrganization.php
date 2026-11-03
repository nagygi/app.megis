<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Services\Auth\ImpersonationService;
use App\Support\CurrentOrganization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Opens an organization's customer pages (/{slug}/…). Opening another of the
 * user's organizations switches to it; a non-member gets "Nincs jogosultságod" (15 · 15.4).
 * Every page and action of a member counts as the organization's last activity (02 · 2.2).
 */
class ResolveOrganization
{
    public function __construct(
        private CurrentOrganization $currentOrganization,
        private ImpersonationService $impersonation,
    ) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->route('organization');
        $user = $request->user();

        abort_unless($organization instanceof Organization, 404);

        $isMember = $user->accessibleOrganizations()->whereKey($organization->id)->exists();

        abort_unless($isMember, 403);

        $this->currentOrganization->set($organization);

        // An admin browsing in the customer's name must not change the customer's last organization,
        // nor count as the organization's activity.
        if (! $this->impersonation->isImpersonating()) {
            if ($user->last_organization_id !== $organization->id) {
                $user->forceFill(['last_organization_id' => $organization->id])->saveQuietly();
            }

            $organization->recordActivity();
        }

        return $next($request);
    }
}
