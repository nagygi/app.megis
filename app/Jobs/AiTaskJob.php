<?php

namespace App\Jobs;

use App\Enums\InboxItemType;
use App\Models\Diagnosis;
use App\Models\Organization;
use App\Services\Inbox\InboxService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Base of every job that runs an AI task (16 · 16.1): three attempts with
 * growing waits on the "ai" queue. When the last attempt fails, the feature
 * shows its own error state and a "Feldolgozási hiba" row opens (22 · 22.2).
 */
abstract class AiTaskJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Below the queue connection's retry_after (config/queue.php).
     */
    public int $timeout = 300;

    public function __construct()
    {
        $this->onQueue('ai');
    }

    /**
     * @return list<int> seconds before the second and the third attempt
     */
    public function backoff(): array
    {
        return [30, 120];
    }

    final public function failed(?Throwable $exception): void
    {
        $this->markFailed($exception);

        app(InboxService::class)->open(
            InboxItemType::ProcessingError,
            $this->failureSummary(),
            organization: $this->failureOrganization(),
            diagnosis: $this->failureDiagnosis(),
            subject: $this->failureSubject(),
            dedupeKey: $this->failureSubject() === null ? null : 'processing-error:'.$this->failureSubject()->getMorphClass().':'.$this->failureSubject()->getKey(),
        );
    }

    /**
     * Puts the feature into its error state, e.g. "Nem tudtuk feldolgozni." (06).
     */
    abstract protected function markFailed(?Throwable $exception): void;

    /**
     * The Beérkező row in one sentence.
     */
    abstract protected function failureSummary(): string;

    protected function failureOrganization(): ?Organization
    {
        return null;
    }

    protected function failureDiagnosis(): ?Diagnosis
    {
        return null;
    }

    /**
     * What failed to process; also keeps the Beérkező from listing it twice.
     */
    protected function failureSubject(): ?Model
    {
        return null;
    }
}
