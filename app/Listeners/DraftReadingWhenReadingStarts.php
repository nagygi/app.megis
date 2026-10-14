<?php

namespace App\Listeners;

use App\Enums\MainDiagnosisStage;
use App\Events\MainDiagnosisStageChanged;
use App\Jobs\DraftReading;

/**
 * When reading starts, the claims and the texts of the reading arrive as
 * AI drafts for the admin (18 · 18.3, 20).
 */
class DraftReadingWhenReadingStarts
{
    public function handle(MainDiagnosisStageChanged $event): void
    {
        // Back from Published (a withdrawal) the reading keeps its texts (21 · 21.5).
        if ($event->to === MainDiagnosisStage::Reading && $event->from !== MainDiagnosisStage::Published) {
            DraftReading::dispatch($event->main);
        }
    }
}
