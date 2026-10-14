<?php

namespace App\Services\Calendar;

use App\Models\CalendarConnection;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * Google Calendar and Microsoft 365 / Outlook, read-only (09 · 9.2). The
 * access token is refreshed when it expired; a refused refresh means the
 * access was withdrawn (09 · 9.6).
 */
class OAuthCalendarSource
{
    /**
     * Read-only scopes; nothing else is asked.
     *
     * @var array<string, list<string>>
     */
    public const SCOPES = [
        CalendarConnection::GOOGLE => ['https://www.googleapis.com/auth/calendar.events.readonly'],
        CalendarConnection::MICROSOFT => ['Calendars.Read', 'offline_access'],
    ];

    /**
     * @return list<CalendarEventData>
     */
    public function events(CalendarConnection $connection, CarbonInterface $from, CarbonInterface $to): array
    {
        $token = $this->accessToken($connection);

        try {
            return $connection->provider === CalendarConnection::GOOGLE
                ? $this->google($token, $from, $to, $connection->user->email)
                : $this->microsoft($token, $from, $to);
        } catch (RequestException $exception) {
            throw new CalendarException(__('calendar.failed'), accessLost: in_array($exception->response->status(), [401, 403], true));
        } catch (ConnectionException) {
            throw new CalendarException(__('calendar.failed'));
        }
    }

    private function accessToken(CalendarConnection $connection): string
    {
        $credentials = $connection->credentials ?? [];

        if (($credentials['expires_at'] ?? 0) > now()->addMinute()->getTimestamp()) {
            return (string) $credentials['access_token'];
        }

        if (blank($credentials['refresh_token'] ?? null)) {
            throw new CalendarException(__('calendar.access_lost'), accessLost: true);
        }

        try {
            $token = Socialite::driver($connection->provider)->refreshToken($credentials['refresh_token']);
        } catch (Throwable) {
            throw new CalendarException(__('calendar.access_lost'), accessLost: true);
        }

        $connection->update(['credentials' => [
            'access_token' => $token->token,
            'refresh_token' => $token->refreshToken ?: $credentials['refresh_token'],
            'expires_at' => now()->addSeconds((int) $token->expiresIn)->getTimestamp(),
        ]]);

        return $token->token;
    }

    /**
     * @return list<CalendarEventData>
     */
    private function google(string $token, CarbonInterface $from, CarbonInterface $to, string $ownerEmail): array
    {
        $events = [];
        $pageToken = null;

        do {
            $response = Http::withToken($token)->timeout(30)->get('https://www.googleapis.com/calendar/v3/calendars/primary/events', array_filter([
                'timeMin' => $from->toIso8601String(),
                'timeMax' => $to->toIso8601String(),
                'singleEvents' => 'true',
                'maxResults' => 2500,
                'pageToken' => $pageToken,
                'fields' => 'nextPageToken,items(id,recurringEventId,summary,description,start,end,attendees(email,displayName,responseStatus,self),status,visibility)',
            ]))->throw()->json();

            foreach ($response['items'] ?? [] as $item) {
                $declined = collect($item['attendees'] ?? [])->contains(fn (array $attendee) => ($attendee['self'] ?? false) && ($attendee['responseStatus'] ?? '') === 'declined');

                if (($item['status'] ?? '') === 'cancelled'
                    || in_array($item['visibility'] ?? 'default', ['private', 'confidential'], true)
                    || ! isset($item['start']['dateTime'])
                    || $declined) {
                    continue;
                }

                $events[] = new CalendarEventData(
                    externalId: (string) $item['id'],
                    seriesKey: $item['recurringEventId'] ?? null,
                    title: mb_substr(trim((string) ($item['summary'] ?? '')), 0, 255) ?: '–',
                    description: filled($item['description'] ?? null) ? mb_substr(strip_tags((string) $item['description']), 0, 5000) : null,
                    startsAt: CarbonImmutable::parse($item['start']['dateTime'])->setTimezone(config('app.timezone')),
                    endsAt: isset($item['end']['dateTime']) ? CarbonImmutable::parse($item['end']['dateTime'])->setTimezone(config('app.timezone')) : null,
                    attendees: collect($item['attendees'] ?? [])
                        ->map(fn (array $attendee) => ['name' => $attendee['displayName'] ?? null, 'email' => isset($attendee['email']) ? strtolower($attendee['email']) : null])
                        ->values()
                        ->all(),
                );
            }

            $pageToken = $response['nextPageToken'] ?? null;
        } while ($pageToken !== null);

        return $events;
    }

    /**
     * @return list<CalendarEventData>
     */
    private function microsoft(string $token, CarbonInterface $from, CarbonInterface $to): array
    {
        $events = [];
        $url = 'https://graph.microsoft.com/v1.0/me/calendarView?'.http_build_query([
            'startDateTime' => $from->toIso8601String(),
            'endDateTime' => $to->toIso8601String(),
            '$top' => 500,
            '$select' => 'id,seriesMasterId,subject,bodyPreview,start,end,attendees,isAllDay,isCancelled,sensitivity,responseStatus',
        ]);

        do {
            $response = Http::withToken($token)->timeout(30)->withHeaders(['Prefer' => 'outlook.timezone="UTC"'])->get($url)->throw()->json();

            foreach ($response['value'] ?? [] as $item) {
                if (($item['isCancelled'] ?? false)
                    || ($item['isAllDay'] ?? false)
                    || in_array($item['sensitivity'] ?? 'normal', ['private', 'confidential'], true)
                    || ($item['responseStatus']['response'] ?? '') === 'declined') {
                    continue;
                }

                $events[] = new CalendarEventData(
                    externalId: (string) $item['id'],
                    seriesKey: $item['seriesMasterId'] ?? null,
                    title: mb_substr(trim((string) ($item['subject'] ?? '')), 0, 255) ?: '–',
                    description: filled($item['bodyPreview'] ?? null) ? mb_substr((string) $item['bodyPreview'], 0, 5000) : null,
                    startsAt: CarbonImmutable::parse($item['start']['dateTime'], 'UTC')->setTimezone(config('app.timezone')),
                    endsAt: isset($item['end']['dateTime']) ? CarbonImmutable::parse($item['end']['dateTime'], 'UTC')->setTimezone(config('app.timezone')) : null,
                    attendees: collect($item['attendees'] ?? [])
                        ->map(fn (array $attendee) => [
                            'name' => $attendee['emailAddress']['name'] ?? null,
                            'email' => isset($attendee['emailAddress']['address']) ? strtolower($attendee['emailAddress']['address']) : null,
                        ])
                        ->values()
                        ->all(),
                );
            }

            $url = $response['@odata.nextLink'] ?? null;
        } while ($url !== null);

        return $events;
    }
}
