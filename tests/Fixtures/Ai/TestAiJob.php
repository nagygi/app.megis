<?php

namespace Tests\Fixtures\Ai;

use App\Jobs\AiTaskJob;
use App\Models\Organization;
use App\Models\Value;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * An AI job for testing the final failure handling.
 */
class TestAiJob extends AiTaskJob
{
    public bool $markedFailed = false;

    public function __construct(private Organization $organization, private Value $subject)
    {
        parent::__construct();
    }

    public function handle(): void {}

    protected function markFailed(?Throwable $exception): void
    {
        $this->markedFailed = true;
    }

    protected function failureSummary(): string
    {
        return 'Nem tudtuk feldolgozni a teszt anyagot.';
    }

    protected function failureOrganization(): ?Organization
    {
        return $this->organization;
    }

    protected function failureSubject(): ?Model
    {
        return $this->subject;
    }
}
