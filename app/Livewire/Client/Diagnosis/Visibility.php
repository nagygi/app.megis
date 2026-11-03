<?php

namespace App\Livewire\Client\Diagnosis;

use App\Enums\DiagnosisStep;
use App\Exceptions\DiagnosisException;
use App\Livewire\Client\Diagnosis\Concerns\InteractsWithMainDiagnosis;
use App\Models\Diagnosis;
use App\Models\Organization;
use App\Services\Diagnoses\ThresholdPolicy;
use App\Services\MainDiagnoses\MainDiagnosisService;
use Illuminate\View\View;
use Livewire\Component;

/**
 * V9 · Ki lát mit (23 · 23.4): who sees what, valid until the end; the only
 * button starts the diagnosis. It only informs, nothing can be switched here.
 * A peer reads their own version from the Profile (31 · 31.5).
 */
class Visibility extends Component
{
    use InteractsWithMainDiagnosis;

    public function mount(Organization $organization, Diagnosis $diagnosis): void
    {
        $this->bootDiagnosis($organization, $diagnosis);
    }

    public function startDiagnosis(MainDiagnosisService $mainDiagnoses): void
    {
        abort_unless($this->isLeader(), 403);

        try {
            $mainDiagnoses->start($this->main(), $this->user());
        } catch (DiagnosisException $exception) {
            session()->flash('error', $exception->getMessage());

            return;
        }

        session()->flash('status', __('diagnosis.visibility.started'));
        $this->redirect($this->overviewUrl(), navigate: false);
    }

    public function render(): View
    {
        if (! $this->isLeader()) {
            return view('livewire.client.diagnosis.visibility', [
                'main' => $this->main(),
                'actors' => $this->peerActors(),
                'isLeader' => false,
            ])->layout('layouts::client', [
                'title' => DiagnosisStep::Visibility->label(),
                'subtitle' => __('diagnosis.visibility.lead'),
                'back' => route('client.profile', $this->organization),
                'backLabel' => __('client.profile.title'),
                'color' => 'cream',
            ])->title(DiagnosisStep::Visibility->label());
        }

        $steps = $this->steps();
        $missing = $steps->firstMissing(except: DiagnosisStep::Visibility);

        return view('livewire.client.diagnosis.visibility', [
            'main' => $this->main(),
            'actors' => $this->leaderActors(),
            'isLeader' => true,
            'missing' => $missing,
            'missingUrl' => $missing === null ? null : $this->stepUrl($missing->step),
            'canStart' => $this->main()->stage->isBeforeRunning() && $missing === null,
        ])->layout('layouts::client', $this->stepLayout(DiagnosisStep::Visibility, __('diagnosis.visibility.lead')))->title(DiagnosisStep::Visibility->label());
    }

    /**
     * The leader's version: Te, A társak (with peers only), A mégis (23 · 23.4).
     *
     * @return array<string, array{sees: string, not: string}>
     */
    private function leaderActors(): array
    {
        $main = $this->main();
        $actors = [
            'you' => [
                'sees' => __($main->show_peer_quotes && $main->hasPeers() ? 'diagnosis.visibility.you_sees_quotes' : 'diagnosis.visibility.you_sees'),
                'not' => __('diagnosis.visibility.you_not'),
            ],
        ];

        if ($main->hasPeers()) {
            $actors['peers'] = ['sees' => __('diagnosis.visibility.peers_sees'), 'not' => __('diagnosis.visibility.peers_not')];
        }

        $actors['megis'] = ['sees' => __('diagnosis.visibility.megis_sees'), 'not' => __('diagnosis.visibility.megis_not')];

        return $actors;
    }

    /**
     * The peer's version (31 · 31.5): the quotes reach the leader only with
     * the A1 switch and the peer's consent (25 · 25.2, 31 · 31.6).
     *
     * @return array<string, array{sees: string, not: string}>
     */
    private function peerActors(): array
    {
        $quotes = in_array($this->user()->id, $this->main()->quotingPeerIds(), true);

        return [
            'you' => ['sees' => __('diagnosis.visibility.peer.you_sees'), 'not' => __('diagnosis.visibility.peer.you_not')],
            'leader' => [
                'sees' => __($quotes ? 'diagnosis.visibility.peer.leader_sees_quotes' : 'diagnosis.visibility.peer.leader_sees', ['count' => ThresholdPolicy::PEERS]),
                'not' => __($quotes ? 'diagnosis.visibility.peer.leader_not_quotes' : 'diagnosis.visibility.peer.leader_not'),
            ],
            'megis' => ['sees' => __('diagnosis.visibility.megis_sees'), 'not' => __('diagnosis.visibility.peer.megis_not')],
        ];
    }
}
