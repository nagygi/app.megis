<?php

namespace App\Http\Middleware;

use App\Services\Legal\LegalDocuments;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * After a document version that must be accepted again comes into force, the
 * customer cannot continue until accepting it (14 · 14.5).
 */
class EnsureLegalAcceptance
{
    public function __construct(private LegalDocuments $legalDocuments) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->legalDocuments->pendingReacceptance($request->user())->isNotEmpty()) {
            return redirect()->guest(route('client.legal.show'));
        }

        return $next($request);
    }
}
