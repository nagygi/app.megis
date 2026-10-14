<?php

use App\Enums\ProcessConfidence;
use App\Enums\ProcessRhythm;
use App\Services\Calendar\CalendarEventData;
use App\Services\Calendar\PatternFinder;
use Carbon\CarbonImmutable;

function calendarEvent(string $title, string $start, ?string $series = null, array $emails = ['anna@example.com', 'bence@example.com', 'dora@example.com']): CalendarEventData
{
    $startsAt = CarbonImmutable::parse($start, 'Europe/Budapest');

    return new CalendarEventData(
        externalId: $title.'@'.$start,
        seriesKey: $series,
        title: $title,
        description: null,
        startsAt: $startsAt,
        endsAt: $startsAt->addMinutes(90),
        attendees: array_map(fn (string $email) => ['name' => null, 'email' => $email], $emails),
    );
}

/**
 * @return list<CalendarEventData>
 */
function weeklyEvents(string $title, int $weeks, ?string $series = null, string $from = '2026-07-14 09:00'): array
{
    return array_map(fn (int $week) => calendarEvent($title, CarbonImmutable::parse($from)->addWeeks($week)->format('Y-m-d H:i'), $series), range(0, $weeks - 1));
}

it('finds a weekly series with its rhythm, length, headcount and occurrence', function () {
    $result = (new PatternFinder)->find(weeklyEvents('Vezetői kör', 12, series: 'uid-1'));

    expect($result['candidates'])->toHaveCount(1);
    $candidate = $result['candidates'][0];

    expect($candidate['key'])->toBe('series:uid-1')
        ->and($candidate['rhythm'])->toBe(ProcessRhythm::Weekly)
        ->and($candidate['rhythm_label'])->toBe('keddenként 9:00')
        ->and($candidate['duration_minutes'])->toBe(90)
        ->and($candidate['headcount'])->toBe(3)
        ->and($candidate['occurrence_label'])->toBe('12/12 hét')
        ->and($candidate['confidence'])->toBe(ProcessConfidence::High);
});

it('groups events with a similar title and a regular rhythm', function () {
    $events = [
        calendarEvent('Ügyfél-review #1', '2026-07-01 10:00'),
        calendarEvent('Ügyfél-review #2', '2026-07-15 10:00'),
        calendarEvent('ügyfél-review #3', '2026-07-29 10:00'),
        calendarEvent('Ügyfél-review #4', '2026-08-12 10:00'),
    ];

    $candidate = (new PatternFinder)->find($events)['candidates'][0];

    expect($candidate['rhythm'])->toBe(ProcessRhythm::Biweekly)
        ->and($candidate['rhythm_label'])->toBe('kéthetente');
});

it('makes no pattern of events whose circle of attendees keeps changing', function () {
    $events = [
        calendarEvent('Egyeztetés', '2026-07-02 14:00', emails: ['a@example.com']),
        calendarEvent('Egyeztetés', '2026-07-09 14:00', emails: ['b@example.com']),
        calendarEvent('Egyeztetés', '2026-07-16 14:00', emails: ['c@example.com', 'd@example.com']),
    ];

    $candidates = (new PatternFinder)->find($events, 90)['candidates'];

    // Three weekly meetings in 90 days, each with other people.
    expect($candidates)->toBeEmpty();
});

it('counts one-off events and events that repeat without a rhythm', function () {
    $events = [
        calendarEvent('Szülinap', '2026-07-03 16:00'),
        calendarEvent('Workshop', '2026-07-05 09:00'),
        calendarEvent('Workshop', '2026-08-30 09:00'),
        ...weeklyEvents('Ops heti', 6, series: 'ops'),
    ];

    $result = (new PatternFinder)->find($events);

    expect($result['one_off'])->toBe(1)
        ->and($result['unlinked'])->toBe(2)
        ->and($result['candidates'])->toHaveCount(1)
        ->and($result['candidates'][0]['confidence'])->not->toBe(ProcessConfidence::High);
});

it('normalizes titles without numbers and punctuation', function () {
    expect((new PatternFinder)->normalize('Vezetői kör #12 (2026.03.02.)'))->toBe('vezetői kör');
});
