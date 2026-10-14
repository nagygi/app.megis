<?php

namespace App\Services\Checks;

class SchedulerCheck implements Check
{
    public function __construct(private Heartbeat $heartbeat) {}

    public function run(): CheckResult
    {
        $label = __('checks.scheduler.label');
        $lastBeatAt = $this->heartbeat->lastBeatAt(Heartbeat::SCHEDULER);

        if ($lastBeatAt === null) {
            return CheckResult::error($label, __('checks.scheduler.missing'));
        }

        $time = $lastBeatAt->format('Y.m.d. H:i:s');

        if (! $this->heartbeat->isFresh(Heartbeat::SCHEDULER, config('megis.health.max_heartbeat_age_seconds'))) {
            return CheckResult::error($label, __('checks.scheduler.stale', ['time' => $time]));
        }

        return CheckResult::ok($label, __('checks.scheduler.ok', ['time' => $time]));
    }
}
