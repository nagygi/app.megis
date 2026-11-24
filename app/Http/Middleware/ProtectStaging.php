<?php

namespace App\Http\Middleware;

use Closure;
use Exot\OnsiteComment\Auth\ParticipantSessions;
use Exot\OnsiteComment\OnsiteComment;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the staging environment private (docs/uzemeltetes.md · 2). Passes:
 * an allowed IP; a participant of the on-page tickets (exot/onsite-comment)
 * with a valid personal session; a signed-in user; the pages a mail link or
 * a signed-out user reaches (login, invitation, password reset, legal pages);
 * every page behind a login, whose own middleware sends a guest to the login.
 * Everyone else (the public pages) needs the staging basic-auth credentials.
 *
 * It runs right after StartSession (bootstrap/app.php), so it can see who is
 * signed in.
 */
class ProtectStaging
{
    /**
     * The routes a new or signed-out user reaches from a mail (MEGIS-8).
     *
     * @var list<string>
     */
    private const ENTRY_ROUTES = [
        'client.login*',
        'client.password.*',
        'client.invitation.*',
        'external.invitation.*',
        'admin.login*',
        'admin.password.setup*',
        'legal.show',
    ];

    public function __construct(
        private readonly ParticipantSessions $onsiteSessions,
        private readonly Router $router,
    ) {}

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

        if ($this->hasOnsiteCommentSession($request)
            || $this->isAccountRoute($request)
            || Auth::guard('web')->check()
            || Auth::guard('admin')->check()) {
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

        // Some browsers never show the prompt: the 401 page says where to log in.
        abort_unless($isAuthorized, 401, 'Staging', ['WWW-Authenticate' => 'Basic realm="megis staging"']);

        return $next($request);
    }

    /**
     * Whether the browser carries a valid on-page ticket session. Testers and
     * the client sign in with their personal invite link, and some browsers
     * never show the basic-auth prompt, so the link alone has to be enough.
     * The lookup never throws; without the `osc_session` cookie it is free.
     */
    private function hasOnsiteCommentSession(Request $request): bool
    {
        return OnsiteComment::enabled() && $this->onsiteSessions->resolve($request) !== null;
    }

    /**
     * Whether the route is a way into an account, or sits behind a login that
     * answers a guest with the login page anyway.
     */
    private function isAccountRoute(Request $request): bool
    {
        $route = $request->route();

        if ($route === null) {
            return false;
        }

        if ($request->routeIs(...self::ENTRY_ROUTES)) {
            return true;
        }

        foreach ($this->router->gatherRouteMiddleware($route) as $middleware) {
            if (is_string($middleware) && is_a(Str::before($middleware, ':'), AuthenticatesRequests::class, true)) {
                return true;
            }
        }

        return false;
    }
}
