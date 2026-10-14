<?php

use App\Enums\CheckStatus;
use App\Services\Checks\Heartbeat;
use App\Services\Checks\QueueWorkerCheck;

beforeEach(function () {
    config(['queue.default' => 'database']);
});

it('passes without a worker when the queue is synchronous', function () {
    config(['queue.default' => 'sync']);

    $result = app(QueueWorkerCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Ok);
});

it('reports an error when the worker never reported', function () {
    $result = app(QueueWorkerCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Error)
        ->and($result->message)->toBe('Még nem jött jel a workertől.');
});

it('reports an error when the last heartbeat is older than five minutes', function () {
    $this->travelTo('2026-10-14 09:00:00');
    app(Heartbeat::class)->record(Heartbeat::QUEUE);
    $this->travelTo('2026-10-14 09:05:01');

    $result = app(QueueWorkerCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Error)
        ->and($result->message)->toBe('Nincs friss jel a workertől (utolsó: 2026.10.14. 09:00:00).');
});

it('passes when the worker reported within five minutes', function () {
    $this->travelTo('2026-10-14 09:00:00');
    app(Heartbeat::class)->record(Heartbeat::QUEUE);
    $this->travelTo('2026-10-14 09:04:00');

    $result = app(QueueWorkerCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Ok);
});
