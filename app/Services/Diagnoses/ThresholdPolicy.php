<?php

namespace App\Services\Diagnoses;

use App\Models\MainDiagnosis;

/**
 * The team threshold (18 · 18.6): the team experience and the gap show only
 * when at least three peers besides the leader answered T4, or when the admin
 * decided on the Futás tab that the diagnosis goes on without it (19).
 */
class ThresholdPolicy
{
    public const PEERS = 3;

    public function peersAnswered(MainDiagnosis $main): int
    {
        return (int) ($main->calculation['peers_answered'] ?? 0);
    }

    public function isMet(MainDiagnosis $main): bool
    {
        return ! $main->hasPeers() || $this->peersAnswered($main) >= self::PEERS;
    }

    /**
     * "Csapat-szám nélkül megy" (19): the report shows the PHI, the gap stays grey.
     */
    public function isOverridden(MainDiagnosis $main): bool
    {
        return $main->threshold_overridden_at !== null;
    }

    /**
     * Whether the client sees the team experience and the gap.
     */
    public function showsTeamExperience(MainDiagnosis $main): bool
    {
        return $this->isMet($main);
    }

    /**
     * "Még {n} társ hangja kell, hogy lássátok a rést."
     */
    public function missingPeers(MainDiagnosis $main): int
    {
        return $this->isMet($main) ? 0 : self::PEERS - $this->peersAnswered($main);
    }
}
