<?php

use App\Enums\CheckStatus;
use App\Services\Checks\Heartbeat;
use App\Services\Checks\SchedulerCheck;

it('reports an error when the scheduler never ran', function () {
    $result = app(SchedulerCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Error)
        ->and($result->message)->toBe('Az ütemező még nem futott.');
});

it('reports an error when the scheduler has not run for over five minutes', function () {
    $this->travelTo('2026-10-14 09:00:00');
    app(Heartbeat::class)->record(Heartbeat::SCHEDULER);
    $this->travelTo('2026-10-14 09:06:00');

    $result = app(SchedulerCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Error);
});

it('passes when the scheduler ran within five minutes', function () {
    $this->travelTo('2026-10-14 09:00:00');
    app(Heartbeat::class)->record(Heartbeat::SCHEDULER);
    $this->travelTo('2026-10-14 09:01:00');

    $result = app(SchedulerCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Ok)
        ->and($result->message)->toBe('Fut (utolsó futás: 2026.10.14. 09:00:00).');
});
