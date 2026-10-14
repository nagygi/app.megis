<?php

namespace App\Console\Commands;

use App\Services\Calendar\CalendarService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Deletes calendar metadata 90 days after connecting; processes and the dates
 * of their occasions stay (09 · 9.5). Scheduled daily (docs/cron.md).
 */
#[Signature('megis:calendar:prune')]
#[Description('A 90 napnál régebbi naptár-metaadat törlése (09 · 9.5)')]
class PruneCalendarMetadataCommand extends Command
{
    public function handle(CalendarService $calendars): int
    {
        $this->info(__('console.calendar_prune.done', ['count' => $calendars->pruneExpired()]));

        return self::SUCCESS;
    }
}
