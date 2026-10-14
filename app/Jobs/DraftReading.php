<?php

namespace App\Jobs;

use App\Models\Diagnosis;
use App\Models\MainDiagnosis;
use App\Models\Organization;
use App\Services\Diagnoses\ReadingDraftService;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * The AI drafts of the reading when it starts (18 · 18.3).
 */
class DraftReading extends AiTaskJob
{
    public function __construct(public MainDiagnosis $main)
    {
        parent::__construct();
    }

    public function handle(ReadingDraftService $drafts): void
    {
        $drafts->draft($this->main->fresh());
    }

    protected function markFailed(?Throwable $exception): void
    {
        app(ReadingDraftService::class)->failed($this->main->fresh());
    }

    protected function failureSummary(): string
    {
        return __('admin.diagnoses.drafts_failed', ['name' => $this->main->diagnosis->name]);
    }

    protected function failureOrganization(): ?Organization
    {
        return $this->main->diagnosis->organization;
    }

    protected function failureDiagnosis(): ?Diagnosis
    {
        return $this->main->diagnosis;
    }

    protected function failureSubject(): ?Model
    {
        return $this->main;
    }
}
