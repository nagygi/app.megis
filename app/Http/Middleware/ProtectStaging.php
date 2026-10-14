<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the staging environment private: an allowed IP passes, everyone else
 * needs the staging basic-auth credentials (docs/uzemeltetes.md · 2).
 */
class ProtectStaging
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('staging')) {
            return $next($request);
        }

        $allowedIps = Config::array('megis.staging.allowed_ips');

        if ($allowedIps !== [] && IpUtils::checkIp((string) $request->ip(), $allowedIps)) {
            return $next($request);
        }

        $user = (string) config('megis.staging.basic_auth_user');
        $password = (string) config('megis.staging.basic_auth_password');

        if ($user === '' || $password === '') {
            // Only an IP allowlist is configured, and this address is not on it.
            abort_if($allowedIps !== [], 403);

            return $next($request);
        }

        $isAuthorized = hash_equals($user, (string) $request->getUser())
            && hash_equals($password, (string) $request->getPassword());

        if (! $isAuthorized) {
            return response('Staging', 401, ['WWW-Authenticate' => 'Basic realm="megis staging"']);
        }

        return $next($request);
    }
}
