<?php

namespace App\Services\Turnstile;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Facades\Log;

/**
 * Server-side verification of a Cloudflare Turnstile token (NFR · 5).
 */
class TurnstileVerifier
{
    public function __construct(private HttpClient $http) {}

    public function verify(?string $token, ?string $ipAddress): bool
    {
        if (blank($token)) {
            return false;
        }

        try {
            $response = $this->http
                ->asForm()
                ->timeout(config('services.turnstile.timeout_seconds'))
                ->post(config('services.turnstile.verify_url'), array_filter([
                    'secret' => config('services.turnstile.secret_key'),
                    'response' => $token,
                    'remoteip' => $ipAddress,
                ]));
        } catch (ConnectionException) {
            Log::channel('auth')->warning('Turnstile verification could not reach Cloudflare.');

            return false;
        }

        return $response->successful() && $response->json('success') === true;
    }
}
