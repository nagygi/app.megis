<?php

namespace App\Services\Calendar;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Reader;
use Throwable;

/**
 * An iCal file or link (09 · 9.2), recurring events expanded for the read
 * period. Private (CLASS), cancelled, all-day events and the ones the owner
 * declined are left out (09 · 9.1, 9.3).
 */
class IcsParser
{
    /**
     * @return list<CalendarEventData>
     */
    public function parse(string $ics, CarbonInterface $from, CarbonInterface $to, ?string $ownerEmail = null): array
    {
        try {
            $calendar = Reader::read($ics, Reader::OPTION_FORGIVING);
        } catch (Throwable) {
            throw new CalendarException(__('calendar.unreadable'));
        }

        if (! $calendar instanceof VCalendar) {
            throw new CalendarException(__('calendar.unreadable'));
        }

        try {
            $expanded = $calendar->expand($from->toDateTimeImmutable(), $to->toDateTimeImmutable());
        } catch (Throwable) {
            $expanded = $calendar;
        }

        $events = [];

        foreach ($expanded->select('VEVENT') as $event) {
            $data = $this->event($event, $ownerEmail);

            if ($data !== null && $data->startsAt->betweenIncluded($from, $to)) {
                $events[] = $data;
            }
        }

        return $events;
    }

    private function event(VEvent $event, ?string $ownerEmail): ?CalendarEventData
    {
        if (! isset($event->DTSTART) || ! $event->DTSTART->hasTime()) {
            return null;
        }

        $status = strtoupper((string) ($event->STATUS ?? ''));
        $class = strtoupper((string) ($event->CLASS ?? 'PUBLIC'));

        if ($status === 'CANCELLED' || in_array($class, ['PRIVATE', 'CONFIDENTIAL'], true)) {
            return null;
        }

        $attendees = [];

        foreach ($event->select('ATTENDEE') as $attendee) {
            $email = strtolower((string) preg_replace('/^mailto:/i', '', (string) $attendee->getValue()));

            if ($ownerEmail !== null && $email === strtolower($ownerEmail) && strtoupper((string) ($attendee['PARTSTAT'] ?? '')) === 'DECLINED') {
                return null;
            }

            $attendees[] = ['name' => isset($attendee['CN']) ? (string) $attendee['CN'] : null, 'email' => $email !== '' ? $email : null];
        }

        $startsAt = CarbonImmutable::instance($event->DTSTART->getDateTime())->setTimezone(config('app.timezone'));
        $endsAt = match (true) {
            isset($event->DTEND) => CarbonImmutable::instance($event->DTEND->getDateTime())->setTimezone(config('app.timezone')),
            isset($event->DURATION) => $startsAt->add($event->DURATION->getDateInterval()),
            default => null,
        };

        $uid = (string) ($event->UID ?? sha1((string) $event->SUMMARY.$startsAt->toIso8601String()));
        $isInstance = isset($event->{'RECURRENCE-ID'}) || isset($event->RRULE);

        return new CalendarEventData(
            externalId: $uid.'@'.$startsAt->format(DateTimeInterface::ATOM),
            seriesKey: $isInstance ? $uid : null,
            title: mb_substr(trim((string) ($event->SUMMARY ?? '')), 0, 255) ?: '–',
            description: $this->text($event->DESCRIPTION ?? null),
            startsAt: $startsAt,
            endsAt: $endsAt,
            attendees: $attendees,
        );
    }

    private function text(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : mb_substr($text, 0, 5000);
    }
}
