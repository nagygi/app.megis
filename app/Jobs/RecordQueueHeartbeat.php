<?php

namespace App\Jobs;

use App\Services\Checks\Heartbeat;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Dispatched by the scheduler every minute; when a worker picks it up,
 * the health check knows the queue is being processed.
 */
class RecordQueueHeartbeat implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function handle(Heartbeat $heartbeat): void
    {
        $heartbeat->record(Heartbeat::QUEUE);
    }
}
