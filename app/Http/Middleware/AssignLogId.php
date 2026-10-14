<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gives every request an identifier that appears in each log line and in the
 * error alert, so a reported error can be found in the logs
 * (docs/uzemeltetes.md · 5).
 */
class AssignLogId
{
    public const CONTEXT_KEY = 'log_id';

    /**
     * Marks requests that came through the HTTP kernel, as opposed to queued
     * jobs or commands, which share the container's request instance.
     */
    public const REQUEST_ATTRIBUTE = 'megis.log_id';

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $logId = (string) Str::uuid();

        Context::add(self::CONTEXT_KEY, $logId);
        $request->attributes->set(self::REQUEST_ATTRIBUTE, $logId);

        return $next($request);
    }
}
