<?php

namespace App\Services\Checks;

class QueueWorkerCheck implements Check
{
    public function __construct(private Heartbeat $heartbeat) {}

    public function run(): CheckResult
    {
        $label = __('checks.queue_worker.label');

        if (config('queue.connections.'.config('queue.default').'.driver') === 'sync') {
            return CheckResult::ok($label, __('checks.queue_worker.sync'));
        }

        $lastBeatAt = $this->heartbeat->lastBeatAt(Heartbeat::QUEUE);

        if ($lastBeatAt === null) {
            return CheckResult::error($label, __('checks.queue_worker.missing'));
        }

        $time = $lastBeatAt->format('Y.m.d. H:i:s');

        if (! $this->heartbeat->isFresh(Heartbeat::QUEUE, config('megis.health.max_heartbeat_age_seconds'))) {
            return CheckResult::error($label, __('checks.queue_worker.stale', ['time' => $time]));
        }

        return CheckResult::ok($label, __('checks.queue_worker.ok', ['time' => $time]));
    }
}
