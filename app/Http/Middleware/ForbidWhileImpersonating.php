<?php

namespace App\Http\Middleware;

use App\Services\Auth\ImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Actions an admin may not take in a customer's name: password and e-mail
 * change, accepting legal documents, giving or withdrawing consent, deleting
 * the account (14 · 14.6).
 */
class ForbidWhileImpersonating
{
    public function __construct(private ImpersonationService $impersonation) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($this->impersonation->isImpersonating(), 403, __('impersonation.forbidden'));

        return $next($request);
    }
}
