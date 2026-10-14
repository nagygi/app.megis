<?php

use App\Jobs\RecordQueueHeartbeat;
use App\Models\UserToken;
use App\Services\Checks\Heartbeat;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
|
| Every task is also documented in docs/cron.md.
|
*/

Schedule::call(fn (Heartbeat $heartbeat) => $heartbeat->record(Heartbeat::SCHEDULER))
    ->everyMinute()
    ->name('heartbeat:scheduler')
    ->onOneServer();

Schedule::job(new RecordQueueHeartbeat)
    ->everyMinute()
    ->name('heartbeat:queue')
    ->onOneServer();

// Expired one-time links and codes are removed a month after expiry (UserToken::prunable).
Schedule::command('model:prune', ['--model' => [UserToken::class]])
    ->daily()
    ->at('03:00')
    ->name('prune:user-tokens')
    ->onOneServer();

// Connected calendars refresh daily until the diagnosis' time window ends (09 · 9.4, 9.5).
Schedule::command('megis:calendar:sync')
    ->dailyAt('04:00')
    ->name('sync:calendars')
    ->onOneServer()
    ->withoutOverlapping();

// Calendar metadata lives 90 days from connecting (09 · 9.5).
Schedule::command('megis:calendar:prune')
    ->dailyAt('03:30')
    ->name('prune:calendar-metadata')
    ->onOneServer();

// The Beérkező's daily scan, then the 8:00 summary to the contacts (22 · 22.2, question 2).
Schedule::command('megis:inbox:scan')
    ->dailyAt('07:30')
    ->name('inbox:scan')
    ->onOneServer()
    ->withoutOverlapping();

Schedule::command('megis:inbox:digest')
    ->dailyAt('08:00')
    ->name('inbox:digest')
    ->onOneServer();

// "Adataim törlése" finishes within 24 hours of the request (31 · 31.7).
Schedule::command('megis:accounts:purge')
    ->hourly()
    ->name('purge:deleted-accounts')
    ->onOneServer()
    ->withoutOverlapping();

// Anonymous Értéktükör runs live 24 hours; only the anonymous statistics stay (06 · 6.1).
Schedule::command('megis:mirror:prune')
    ->hourly()
    ->name('prune:mirror-sessions')
    ->onOneServer();
