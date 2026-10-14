<?php

namespace App\Services\Checks;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository;

/**
 * Liveness marks written by the scheduler and the queue worker every minute,
 * read by the health check (docs/uzemeltetes.md · 3).
 */
class Heartbeat
{
    public const SCHEDULER = 'scheduler';

    public const QUEUE = 'queue';

    public function __construct(private Repository $cache) {}

    public function record(string $name): void
    {
        $this->cache->forever($this->key($name), now()->getTimestamp());
    }

    public function lastBeatAt(string $name): ?CarbonImmutable
    {
        $timestamp = $this->cache->get($this->key($name));

        return $timestamp === null ? null : CarbonImmutable::createFromTimestamp($timestamp, config('app.timezone'));
    }

    public function isFresh(string $name, int $maxAgeSeconds): bool
    {
        $lastBeatAt = $this->lastBeatAt($name);

        return $lastBeatAt !== null && $lastBeatAt->greaterThanOrEqualTo(now()->subSeconds($maxAgeSeconds));
    }

    private function key(string $name): string
    {
        return 'megis:heartbeat:'.$name;
    }
}
