<?php

namespace App\Http\Controllers;

use App\Services\Checks\CheckResult;
use App\Services\Checks\CheckRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * GET /health for the uptime monitor (docs/uzemeltetes.md · 3).
 *
 * Outsiders only see "ok" or "fail"; the details need the health token.
 */
class HealthController extends Controller
{
    public function __invoke(Request $request, CheckRunner $runner): Response|JsonResponse
    {
        $results = $runner->run(config('megis.health.checks'));
        $isHealthy = $results->doesntContain(fn (CheckResult $result): bool => $result->isError());
        $status = $isHealthy ? 200 : 503;

        if ($this->hasValidToken($request)) {
            return response()
                ->json([
                    'status' => $isHealthy ? 'ok' : 'fail',
                    'version' => config('megis.version'),
                    'environment' => app()->environment(),
                    'checks' => $results->map->toArray(),
                ], $status)
                ->header('Cache-Control', 'no-store');
        }

        return response($isHealthy ? 'ok' : 'fail', $status)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'no-store');
    }

    private function hasValidToken(Request $request): bool
    {
        $expectedToken = (string) config('megis.health.token');
        $givenToken = (string) ($request->header('X-Health-Token') ?? $request->query('token', ''));

        return $expectedToken !== '' && hash_equals($expectedToken, $givenToken);
    }
}
