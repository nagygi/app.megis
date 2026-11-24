<?php

namespace App\Http\Middleware;

use App\Services\Audit\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs which user did what and when (docs/uzemeltetes.md · 4). It closes the
 * web group, so the session is read already; it wraps the whole request,
 * so a Livewire update is logged after its components ran.
 */
class LogUserActivity
{
    public function __construct(private ActivityLogger $activity) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $actors = $this->activity->actorsOf($request);

        $response = $next($request);

        $this->activity->record($request, $response, $actors);

        return $response;
    }
}
