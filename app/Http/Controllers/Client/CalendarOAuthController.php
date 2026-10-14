<?php

namespace App\Http\Controllers\Client;

use App\Enums\ParticipantRole;
use App\Http\Controllers\Controller;
use App\Models\CalendarConnection;
use App\Models\Diagnosis;
use App\Models\Organization;
use App\Services\Calendar\CalendarException;
use App\Services\Calendar\CalendarService;
use App\Services\Calendar\OAuthCalendarSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * Google and Microsoft calendar access, read-only (09 · 9.2). The consent
 * screen shows the mégis; the callback lands on V6·d, or back on V6·b with
 * "Most nem sikerült" or the IT admin note (09 · 9.6).
 */
class CalendarOAuthController extends Controller
{
    private const SESSION_KEY = 'calendar_oauth';

    public function redirect(Request $request, Organization $organization, Diagnosis $diagnosis, string $provider): SymfonyRedirect
    {
        abort_unless($this->isConfigured($provider), 404);

        $main = $diagnosis->main;
        abort_unless($main !== null && $diagnosis->organization_id === $organization->id && $main->participantFor($request->user(), ParticipantRole::Leader) !== null, 403);

        $request->session()->put(self::SESSION_KEY, ['organization' => $organization->id, 'diagnosis' => $diagnosis->id, 'provider' => $provider]);

        $driver = Socialite::driver($provider)
            ->redirectUrl(route('client.calendar.callback', $provider))
            ->scopes(OAuthCalendarSource::SCOPES[$provider]);

        if ($provider === CalendarConnection::GOOGLE) {
            $driver->with(['access_type' => 'offline', 'prompt' => 'consent']);
        }

        return $driver->redirect();
    }

    public function callback(Request $request, string $provider, CalendarService $calendars): RedirectResponse
    {
        $state = $request->session()->pull(self::SESSION_KEY);
        abort_if($state === null || ($state['provider'] ?? null) !== $provider || ! $this->isConfigured($provider), 404);

        $organization = Organization::query()->findOrFail($state['organization']);
        $diagnosis = Diagnosis::query()->whereBelongsTo($organization)->findOrFail($state['diagnosis']);
        $main = $diagnosis->main;
        abort_unless($main !== null && $main->participantFor($request->user(), ParticipantRole::Leader) !== null, 403);

        $calendarPage = route('client.diagnosis.process-calendar', [$organization, $diagnosis]);

        // A company account whose IT admin has to approve the access (09 · 9.2).
        if ($request->filled('error')) {
            $needsAdmin = str_contains((string) $request->input('error_description'), 'AADSTS65001')
                || str_contains((string) $request->input('error_description'), 'AADSTS90094')
                || $request->input('error') === 'consent_required';

            return redirect($calendarPage)->with('calendar_error', $needsAdmin ? __('calendar.admin_consent') : __('calendar.failed'));
        }

        try {
            $user = Socialite::driver($provider)->redirectUrl(route('client.calendar.callback', $provider))->user();

            $calendars->connectOAuth($request->user(), $organization, $main, $provider, [
                'access_token' => $user->token,
                'refresh_token' => $user->refreshToken,
                'expires_at' => now()->addSeconds((int) ($user->expiresIn ?? 3600))->getTimestamp(),
            ]);
        } catch (CalendarException $exception) {
            return redirect($calendarPage)->with('calendar_error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return redirect($calendarPage)->with('calendar_error', __('calendar.failed'));
        }

        return redirect()->route('client.diagnosis.process-list', [$organization, $diagnosis]);
    }

    private function isConfigured(string $provider): bool
    {
        return in_array($provider, [CalendarConnection::GOOGLE, CalendarConnection::MICROSOFT], true)
            && filled(config('services.'.$provider.'.client_id'))
            && filled(config('services.'.$provider.'.client_secret'));
    }
}
