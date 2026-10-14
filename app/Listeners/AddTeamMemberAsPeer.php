<?php

namespace App\Listeners;

use App\Events\TeamMemberAdded;
use App\Services\MainDiagnoses\MainDiagnosisService;

/**
 * Whoever joins the team while its diagnosis runs becomes a peer until
 * reading starts, and gets the invitation once the diagnosis runs (17 · 17.2, 25 · 25.1).
 */
class AddTeamMemberAsPeer
{
    public function __construct(private MainDiagnosisService $mainDiagnoses) {}

    public function handle(TeamMemberAdded $event): void
    {
        $this->mainDiagnoses->teamMemberAdded($event->team, $event->user);
    }
}
