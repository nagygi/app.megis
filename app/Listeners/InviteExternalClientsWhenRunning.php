<?php

namespace App\Listeners;

use App\Enums\MainDiagnosisStage;
use App\Events\MainDiagnosisStageChanged;
use App\Services\ExternalClients\ExternalClientMeasurementService;

/**
 * When the diagnosis starts running, the users of its clients get their
 * personal link to the Ügyfélmérés (35 · 35.5).
 */
class InviteExternalClientsWhenRunning
{
    public function __construct(private ExternalClientMeasurementService $measurements) {}

    public function handle(MainDiagnosisStageChanged $event): void
    {
        if ($event->to === MainDiagnosisStage::Running) {
            $this->measurements->inviteClients($event->main);
        }
    }
}
