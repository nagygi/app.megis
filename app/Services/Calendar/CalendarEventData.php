<?php

namespace App\Services\Calendar;

use Carbon\CarbonImmutable;

/**
 * One event as every source gives it: only what 09 · 9.1 reads, never
 * attachments; private, cancelled, declined and all-day events never get here.
 */
final readonly class CalendarEventData
{
    /**
     * @param  list<array{name: ?string, email: ?string}>  $attendees
     */
    public function __construct(
        public string $externalId,
        public ?string $seriesKey,
        public string $title,
        public ?string $description,
        public CarbonImmutable $startsAt,
        public ?CarbonImmutable $endsAt,
        public array $attendees,
    ) {}
}
