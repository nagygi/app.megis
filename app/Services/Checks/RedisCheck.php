<?php

namespace App\Services\Checks;

use App\Services\Checks\Concerns\DescribesExceptions;
use Illuminate\Redis\RedisManager;
use Throwable;

class RedisCheck implements Check
{
    use DescribesExceptions;

    public function __construct(private RedisManager $redis) {}

    public function run(): CheckResult
    {
        $label = __('checks.redis.label');

        if (! $this->redisIsUsed()) {
            return CheckResult::ok($label, __('checks.redis.unused'));
        }

        try {
            $this->redis->connection()->ping();

            return CheckResult::ok($label, __('checks.redis.ok'));
        } catch (Throwable $exception) {
            return CheckResult::error($label, __('checks.redis.error', ['error' => $this->describe($exception)]));
        }
    }

    private function redisIsUsed(): bool
    {
        $drivers = [
            config('cache.stores.'.config('cache.default').'.driver'),
            config('session.driver'),
            config('queue.connections.'.config('queue.default').'.driver'),
        ];

        return in_array('redis', $drivers, true);
    }
}
