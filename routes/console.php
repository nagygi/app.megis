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
