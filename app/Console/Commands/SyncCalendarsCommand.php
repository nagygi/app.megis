<?php

namespace App\Console\Commands;

use App\Models\CalendarConnection;
use App\Services\Calendar\CalendarService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

/**
 * The daily calendar refresh until the time window ends: new occasions for
 * the accepted processes, automatic disconnect at the end (09 · 9.4, 9.5).
 */
#[Signature('megis:calendar:sync')]
#[Description('Összekötött naptárak napi frissítése (09 · 9.4)')]
class SyncCalendarsCommand extends Command
{
    public function handle(CalendarService $calendars): int
    {
        $count = 0;

        CalendarConnection::query()
            ->where('status', CalendarConnection::ACTIVE)
            ->where('provider', '!=', CalendarConnection::ICS_FILE)
            ->with(['user', 'organization', 'mainDiagnosis'])
            ->each(function (CalendarConnection $connection) use ($calendars, &$count): void {
                try {
                    $calendars->sync($connection);
                    $count++;
                } catch (Throwable $exception) {
                    report($exception);
                }
            });

        $this->info(__('console.calendar_sync.done', ['count' => $count]));

        return self::SUCCESS;
    }
}
