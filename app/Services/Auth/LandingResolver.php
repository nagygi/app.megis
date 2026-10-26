<?php

namespace App\Services\Auth;

use App\Enums\MainDiagnosisStage;
use App\Enums\ParticipantRole;
use App\Models\MainDiagnosis;
use App\Models\MirrorSession;
use App\Models\Organization;
use App\Models\User;

/**
 * Where a customer arrives after logging in (14 · 14.4). The system picks the
 * page, not the user: the diagnosis the user leads (V1 while filling in,
 * later its home, V12 when published), otherwise the one they are a peer in
 * (T1, T5; 26 · 26.4).
 * Someone who has only made an Értéktükör sees their latest mirror, its
 * values expanded and the full picture (06 · 6.1); otherwise the organization
 * home opens.
 */
class LandingResolver
{
    public function urlFor(User $user): string
    {
        $organization = $this->organizationFor($user);

        if ($organization === null) {
            return route('client.paused');
        }

        if ($main = $this->diagnosisFor($user, $organization)) {
            return route('client.diagnosis.show', [$organization, $main->diagnosis]);
        }

        $mirror = $organization->isIndividual()
            ? MirrorSession::query()->where('user_id', $user->id)->where('organization_id', $organization->id)->whereNotNull('claimed_at')->latest('id')->first()
            : null;

        if ($mirror !== null) {
            return route('client.mirror.result', [$organization, $mirror]);
        }

        return route('client.home', $organization);
    }

    /**
     * The last used organization, or the first accessible one (15 · 15.4).
     */
    public function organizationFor(User $user): ?Organization
    {
        $organizations = $user->accessibleOrganizations()->orderBy('organizations.name')->get();

        return $organizations->firstWhere('id', $user->last_organization_id) ?? $organizations->first();
    }

    /**
     * The latest main diagnosis the user leads in the organization; if none, the
     * latest running one they are a peer in (26 · 26.4). After them a published
     * one: the leader's V12, the peer's T5 with the Útravaló (B2, 27, 29). The
     * organization home opens the same one.
     */
    public function diagnosisFor(User $user, Organization $organization): ?MainDiagnosis
    {
        $query = fn (ParticipantRole $role, array $stages) => MainDiagnosis::query()
            ->withParticipant($user, $role)
            ->inStages(...$stages)
            ->whereHas('diagnosis', fn ($diagnosis) => $diagnosis->where('organization_id', $organization->id))
            ->with('diagnosis')
            ->latest('id')
            ->first();

        return $query(ParticipantRole::Leader, [MainDiagnosisStage::Invited, MainDiagnosisStage::Filling, MainDiagnosisStage::Running, MainDiagnosisStage::Reading])
            ?? $query(ParticipantRole::Peer, [MainDiagnosisStage::Running, MainDiagnosisStage::Reading])
            ?? $query(ParticipantRole::Leader, [MainDiagnosisStage::Published])
            ?? $query(ParticipantRole::Peer, [MainDiagnosisStage::Published]);
    }
}
