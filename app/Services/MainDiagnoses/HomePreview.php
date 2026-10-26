<?php

namespace App\Services\MainDiagnoses;

use App\Enums\MainDiagnosisStage;
use App\Enums\MainDiagnosisSubject;
use App\Enums\ParticipantRole;
use App\Models\MainDiagnosis;
use App\Models\MainDiagnosisParticipant;
use App\Models\Organization;
use App\Models\User;

/**
 * The organization home before a diagnosis opens for the user (26 · 26.4,
 * prototype B2): the way ahead, grey and not clickable. The leader of a
 * diagnosis not yet running, or an organization leader without a role in
 * one, sees the leader's steps and under them the team's; everyone else the
 * peer's. Once the diagnosis opens, the home is its page (V1, T1).
 *
 * @phpstan-type Preview array{
 *     role: 'leader'|'peer',
 *     main: ?MainDiagnosis,
 *     leaderSteps: ?StepList,
 *     peerSteps: ?StepList,
 *     team: ?string,
 *     members: list<string>,
 *     leaderName: ?string,
 *     subject: ?string,
 * }
 */
class HomePreview
{
    public function __construct(
        private MainDiagnosisService $mainDiagnoses,
        private StepProgress $progress,
    ) {}

    /**
     * @return Preview
     */
    public function for(Organization $organization, User $user): array
    {
        if ($main = $this->notStarted($organization, $user, ParticipantRole::Leader)) {
            return $this->leader($organization, $this->progress->forLeader($main, $user), $main, $main);
        }

        if ($main = $this->notStarted($organization, $user, ParticipantRole::Peer)) {
            return [
                'role' => 'peer',
                'main' => $main,
                'leaderSteps' => null,
                'peerSteps' => $this->progress->forPeer($main, $user),
                'team' => $main->diagnosis->team?->name,
                'members' => [],
                'leaderName' => $main->leader?->name,
                'subject' => $main->subjectName(),
            ];
        }

        if ($organization->isIndividual() || $this->mainDiagnoses->isOrganizationLeader($organization, $user)) {
            // The team of the diagnosis being set up in the organization, if there is one.
            $teamOf = $this->notStarted($organization);
            $subject = $organization->isIndividual() ? MainDiagnosisSubject::Individual : ($teamOf?->subject ?? MainDiagnosisSubject::Team);

            return $this->leader($organization, $this->mainDiagnoses->previewSteps($organization, $subject, $user), null, $teamOf);
        }

        return [
            'role' => 'peer',
            'main' => null,
            'leaderSteps' => null,
            'peerSteps' => $this->mainDiagnoses->previewPeerSteps($organization->product?->key),
            'team' => null,
            'members' => [],
            'leaderName' => null,
            'subject' => null,
        ];
    }

    /**
     * The leader's steps, and the team's under them while the product has peers.
     *
     * @return Preview
     */
    private function leader(Organization $organization, StepList $steps, ?MainDiagnosis $main, ?MainDiagnosis $teamOf): array
    {
        $productKey = $teamOf?->product_key ?? $organization->product?->key;
        $hasPeers = $teamOf !== null
            ? $teamOf->hasPeers()
            : ! $organization->isIndividual() && (bool) config('diagnosis.products.'.$productKey.'.has_peers', false);

        return [
            'role' => 'leader',
            'main' => $main,
            'leaderSteps' => $steps,
            'peerSteps' => $hasPeers ? $this->mainDiagnoses->previewPeerSteps($productKey) : null,
            'team' => $hasPeers ? $teamOf?->diagnosis->team?->name : null,
            'members' => $hasPeers && $teamOf !== null
                ? $teamOf->peers()->with('user')->get()->map(fn (MainDiagnosisParticipant $peer) => (string) $peer->user?->name)->filter()->values()->all()
                : [],
            'leaderName' => null,
            'subject' => null,
        ];
    }

    /**
     * The newest diagnosis of the organization not running yet; with a user
     * and a role, one they take part in that way.
     */
    private function notStarted(Organization $organization, ?User $user = null, ?ParticipantRole $role = null): ?MainDiagnosis
    {
        return MainDiagnosis::query()
            ->when($user !== null, fn ($query) => $query->withParticipant($user, $role))
            ->inStages(MainDiagnosisStage::Draft, MainDiagnosisStage::Invited, MainDiagnosisStage::Filling)
            ->whereHas('diagnosis', fn ($query) => $query->where('organization_id', $organization->id))
            ->with('diagnosis.team', 'diagnosis.organization', 'leader')
            ->latest('id')
            ->first();
    }
}
