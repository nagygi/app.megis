<?php

namespace App\Events;

use App\Enums\MainDiagnosisStage;
use App\Models\MainDiagnosis;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A main diagnosis moved to another stage (17 · 17.5): reading starts the
 * drafts (18 · 18.3), the Beérkező closes what is done (22 · 22.3).
 */
class MainDiagnosisStageChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public MainDiagnosis $main,
        public MainDiagnosisStage $from,
        public MainDiagnosisStage $to,
        public ?User $actor = null,
    ) {}
}
