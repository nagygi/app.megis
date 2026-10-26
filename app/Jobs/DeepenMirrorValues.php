<?php

namespace App\Jobs;

use App\Enums\MirrorInsightStatus;
use App\Models\MirrorSession;
use App\Models\Organization;
use App\Services\Materials\MaterialPipeline;
use App\Services\Mirror\MirrorInsightService;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * ET4 · the mégis Coach's suggestions for a mirror, after its measurement
 * (NM-06). A job of its own: when it fails, ET4 shows the Értéktár's words.
 */
class DeepenMirrorValues extends AiTaskJob
{
    public function __construct(public MirrorSession $session)
    {
        parent::__construct();
    }

    public function handle(MirrorInsightService $insights, MaterialPipeline $pipeline): void
    {
        $material = $this->session->material;

        if ($material === null) {
            return;
        }

        $insights->deepen($this->session, $pipeline->contextFor($material));
    }

    protected function failureKind(): ?string
    {
        return 'mirror_deepening';
    }

    protected function markFailed(?Throwable $exception): void
    {
        $this->session->update(['insight_status' => MirrorInsightStatus::Failed]);
    }

    protected function failureSummary(): string
    {
        return __('mirror.deepening_failure_summary', ['date' => $this->session->created_at->format('Y.m.d. H:i')]);
    }

    protected function failureOrganization(): ?Organization
    {
        return $this->session->organization;
    }

    protected function failureSubject(): ?Model
    {
        return $this->session->material;
    }
}
