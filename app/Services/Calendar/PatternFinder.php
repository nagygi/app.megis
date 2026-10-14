<?php

namespace App\Services\Calendar;

use App\Enums\ProcessConfidence;
use App\Enums\ProcessRhythm;

/**
 * The rule-based part of the detection (09 · 9.3): a candidate is a
 * recurring series, or events with a similar title and circle of attendees
 * that follow each other regularly. What does not make a pattern is counted:
 * one-off events and events that repeat without a rhythm ("nincs hozzákötve").
 */
class PatternFinder
{
    private const WEEKDAYS = [
        1 => 'hétfőnként', 2 => 'keddenként', 3 => 'szerdánként', 4 => 'csütörtökönként',
        5 => 'péntekenként', 6 => 'szombatonként', 7 => 'vasárnaponként',
    ];

    /**
     * @param  list<CalendarEventData>  $events
     * @return array{candidates: list<array<string, mixed>>, one_off: int, unlinked: int}
     */
    public function find(array $events, int $windowDays = 90): array
    {
        usort($events, fn (CalendarEventData $a, CalendarEventData $b) => $a->startsAt <=> $b->startsAt);

        $series = [];
        $byTitle = [];

        foreach ($events as $event) {
            if ($event->seriesKey !== null) {
                $series[$event->seriesKey][] = $event;
            } else {
                $byTitle[$this->normalize($event->title)][] = $event;
            }
        }

        // A series with one event left in the period is an ordinary event.
        foreach ($series as $key => $group) {
            if (count($group) < 2) {
                unset($series[$key]);
                $byTitle[$this->normalize($group[0]->title)][] = $group[0];
            }
        }

        $candidates = [];
        $oneOff = 0;
        $unlinked = 0;

        foreach ($series as $key => $group) {
            $candidates[] = $this->candidate('series:'.$key, $group, $windowDays, isSeries: true);
        }

        foreach ($byTitle as $title => $group) {
            usort($group, fn (CalendarEventData $a, CalendarEventData $b) => $a->startsAt <=> $b->startsAt);

            if (count($group) === 1) {
                $oneOff++;

                continue;
            }

            if (count($group) < 3 || ! $this->isRegular($group) || $this->stability($group) < 0.4) {
                $unlinked += count($group);

                continue;
            }

            $candidates[] = $this->candidate('title:'.$title, $group, $windowDays, isSeries: false);
        }

        return ['candidates' => $candidates, 'one_off' => $oneOff, 'unlinked' => $unlinked];
    }

    /**
     * Lower case, without numbers, dates and punctuation, so "Vezetői kör #12" and "vezetői kör" are one.
     */
    public function normalize(string $title): string
    {
        $title = mb_strtolower($title);
        $title = (string) preg_replace('/[0-9]+/u', ' ', $title);
        $title = (string) preg_replace('/[^\p{L}\s]+/u', ' ', $title);

        return trim((string) preg_replace('/\s+/u', ' ', $title));
    }

    /**
     * @param  list<CalendarEventData>  $group
     * @return array<string, mixed>
     */
    private function candidate(string $key, array $group, int $windowDays, bool $isSeries): array
    {
        $interval = $this->medianInterval($group);
        $rhythm = $this->rhythm($interval);
        $count = count($group);
        $expected = $interval > 0 ? max($count, (int) floor($windowDays / $interval)) : $count;
        $ratio = $expected > 0 ? $count / $expected : 1.0;
        $stability = $this->stability($group);

        $confidence = match (true) {
            $ratio >= 0.66 && $stability >= 0.6 => ProcessConfidence::High,
            $ratio >= 0.4 && $stability >= 0.4 => ProcessConfidence::Medium,
            default => ProcessConfidence::Low,
        };

        $titles = array_count_values(array_map(fn (CalendarEventData $event) => $event->title, $group));
        arsort($titles);
        $description = collect($group)->map(fn (CalendarEventData $event) => $event->description)->filter()->first();
        $headcounts = array_map(fn (CalendarEventData $event) => count($event->attendees), $group);
        $durations = array_filter(array_map(fn (CalendarEventData $event) => $event->endsAt === null ? null : (int) round($event->startsAt->diffInMinutes($event->endsAt)), $group));

        return [
            'key' => $key,
            'title' => (string) array_key_first($titles),
            'description' => $description,
            'rhythm' => $rhythm,
            'rhythm_label' => $this->rhythmLabel($rhythm, $interval, $group),
            'duration_minutes' => $durations === [] ? null : (int) round($this->median(array_values($durations))),
            'headcount' => max($headcounts) === 0 ? null : max(1, (int) round($this->median($headcounts))),
            'occurrence_label' => $this->occurrenceLabel($rhythm, $count, $expected),
            'confidence' => $confidence,
            'is_series' => $isSeries,
            'events' => $group,
            'attendees' => $this->regularAttendees($group),
        ];
    }

    /**
     * Most gaps are close to the typical one.
     *
     * @param  list<CalendarEventData>  $group
     */
    private function isRegular(array $group): bool
    {
        $intervals = $this->intervals($group);
        $median = $this->median($intervals);

        if ($median <= 0) {
            return false;
        }

        $close = array_filter($intervals, fn (float $days) => abs($days - $median) <= max(1.5, $median * 0.4));

        return count($close) / count($intervals) >= 0.5;
    }

    /**
     * How much the circle of attendees stays the same: the average overlap with
     * the people who are there at least half of the time. 1 without attendees.
     *
     * @param  list<CalendarEventData>  $group
     */
    private function stability(array $group): float
    {
        $regular = array_column($this->regularAttendees($group, 0.5), 'email');

        if ($regular === []) {
            // Nobody comes regularly: a stable circle only if there are no attendees at all.
            return collect($group)->every(fn (CalendarEventData $event) => $event->attendees === []) ? 1.0 : 0.0;
        }

        $scores = array_map(function (CalendarEventData $event) use ($regular): float {
            $emails = array_filter(array_column($event->attendees, 'email'));
            $union = array_unique([...$regular, ...$emails]);

            return $union === [] ? 1.0 : count(array_intersect($regular, $emails)) / count($union);
        }, $group);

        return array_sum($scores) / count($scores);
    }

    /**
     * @param  list<CalendarEventData>  $group
     * @return list<array{name: ?string, email: string}>
     */
    private function regularAttendees(array $group, float $share = 0.3): array
    {
        $seen = [];

        foreach ($group as $event) {
            foreach ($event->attendees as $attendee) {
                if (filled($attendee['email'] ?? null)) {
                    $seen[$attendee['email']] ??= ['name' => $attendee['name'] ?? null, 'email' => $attendee['email'], 'count' => 0];
                    $seen[$attendee['email']]['count']++;
                }
            }
        }

        return array_values(array_map(
            fn (array $attendee) => ['name' => $attendee['name'], 'email' => $attendee['email']],
            array_filter($seen, fn (array $attendee) => $share <= $attendee['count'] / count($group)),
        ));
    }

    /**
     * @param  list<CalendarEventData>  $group
     * @return list<float>
     */
    private function intervals(array $group): array
    {
        $intervals = [];

        for ($index = 1; $index < count($group); $index++) {
            $intervals[] = $group[$index - 1]->startsAt->diffInMinutes($group[$index]->startsAt) / 1440;
        }

        return $intervals;
    }

    /**
     * @param  list<CalendarEventData>  $group
     */
    private function medianInterval(array $group): float
    {
        $intervals = $this->intervals($group);

        return $intervals === [] ? 0.0 : (float) $this->median($intervals);
    }

    private function rhythm(float $days): ProcessRhythm
    {
        return match (true) {
            $days <= 0 => ProcessRhythm::Occasional,
            $days <= 10 => ProcessRhythm::Weekly,
            $days <= 18 => ProcessRhythm::Biweekly,
            $days <= 45 => ProcessRhythm::Monthly,
            $days <= 120 => ProcessRhythm::Quarterly,
            default => ProcessRhythm::Occasional,
        };
    }

    /**
     * "keddenként 9:00", "kéthetente", "havonta" (09 · 9.3).
     *
     * @param  list<CalendarEventData>  $group
     */
    private function rhythmLabel(ProcessRhythm $rhythm, float $days, array $group): string
    {
        $time = $this->mode(array_map(fn (CalendarEventData $event) => $event->startsAt->format('G:i'), $group));

        return match (true) {
            $rhythm === ProcessRhythm::Weekly && $days < 2 => 'naponta '.$time,
            $rhythm === ProcessRhythm::Weekly => self::WEEKDAYS[(int) $this->mode(array_map(fn (CalendarEventData $event) => $event->startsAt->isoWeekday(), $group))].' '.$time,
            $rhythm === ProcessRhythm::Biweekly => 'kéthetente',
            $rhythm === ProcessRhythm::Monthly => 'havonta',
            $rhythm === ProcessRhythm::Quarterly => 'negyedévente',
            default => 'alkalmanként',
        };
    }

    /**
     * "8/12 hét", "6/6 alkalom", "3/3 hónap" (09 · 9.3).
     */
    private function occurrenceLabel(ProcessRhythm $rhythm, int $count, int $expected): string
    {
        return match ($rhythm) {
            ProcessRhythm::Weekly => $count.'/'.$expected.' hét',
            ProcessRhythm::Monthly => $count.'/'.$expected.' hónap',
            default => $count.'/'.$expected.' alkalom',
        };
    }

    /**
     * @param  list<int|float>  $numbers
     */
    private function median(array $numbers): int|float
    {
        sort($numbers);
        $count = count($numbers);

        if ($count === 0) {
            return 0;
        }

        $middle = intdiv($count, 2);

        return $count % 2 === 1 ? $numbers[$middle] : ($numbers[$middle - 1] + $numbers[$middle]) / 2;
    }

    /**
     * @param  list<int|string>  $values
     */
    private function mode(array $values): int|string
    {
        $counts = array_count_values($values);
        arsort($counts);

        return array_key_first($counts);
    }
}
