<?php

namespace App\Listeners;

use App\Enums\MainDiagnosisStage;
use App\Events\MainDiagnosisStageChanged;
use App\Services\Inbox\InboxService;

/**
 * Rows whose cause ends with a stage close by themselves (22 · 22.3): the
 * diagnosis started, reading began, it was published or closed.
 */
class CloseInboxRowsOnStageChange
{
    public function __construct(private InboxService $inbox) {}

    public function handle(MainDiagnosisStageChanged $event): void
    {
        $id = $event->main->id;

        $keys = match ($event->to) {
            MainDiagnosisStage::Running => ['completion:'.$id],
            MainDiagnosisStage::Reading => ['completion:'.$id, 'threshold:'.$id],
            MainDiagnosisStage::Published, MainDiagnosisStage::Closed => ['completion:'.$id, 'threshold:'.$id, 'publication:'.$id],
            default => [],
        };

        foreach ($keys as $key) {
            $this->inbox->closeAutomatically($key);
        }

        // Invitations stop mattering once reading starts.
        if (in_array($event->to, [MainDiagnosisStage::Reading, MainDiagnosisStage::Published, MainDiagnosisStage::Closed], true)) {
            foreach ($event->main->participants()->pluck('id') as $participantId) {
                $this->inbox->closeAutomatically('invitation:'.$participantId);
            }
        }
    }
}
