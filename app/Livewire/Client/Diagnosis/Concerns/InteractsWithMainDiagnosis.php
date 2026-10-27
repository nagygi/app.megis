<?php

namespace App\Livewire\Client\Diagnosis\Concerns;

use App\Enums\DiagnosisStep;
use App\Enums\MainDiagnosisStage;
use App\Enums\ParticipantRole;
use App\Enums\StepMark;
use App\Models\Diagnosis;
use App\Models\MainDiagnosis;
use App\Models\MainDiagnosisParticipant;
use App\Models\Organization;
use App\Models\User;
use App\Services\Auth\ImpersonationService;
use App\Services\MainDiagnoses\StepList;
use App\Services\MainDiagnoses\StepProgress;

/**
 * The frame of the filling pages (23 · 23.3, 25): who the user is in this
 * diagnosis, the steps, the step counter and the way back and forward.
 * Only the diagnosis' leader and its peers may enter.
 */
trait InteractsWithMainDiagnosis
{
    public Organization $organization;

    public Diagnosis $diagnosis;

    public string $role = '';

    protected function bootDiagnosis(Organization $organization, Diagnosis $diagnosis): void
    {
        $main = $diagnosis->main;

        abort_if($main === null || $diagnosis->organization_id !== $organization->id, 404);
        abort_if(in_array($main->stage, [MainDiagnosisStage::Draft, MainDiagnosisStage::Closed], true), 404);

        $user = $this->user();
        $participant = $main->participantFor($user, ParticipantRole::Leader) ?? $main->participantFor($user, ParticipantRole::Peer);

        abort_if($participant === null, 403);
        // Peers are invited when the diagnosis starts running (25 · 25.1).
        abort_if($participant->role === ParticipantRole::Peer && $main->stage->isBeforeRunning(), 404);

        $this->organization = $organization;
        $this->diagnosis = $diagnosis;
        $this->role = $participant->role->value;
    }

    public function main(): MainDiagnosis
    {
        return $this->diagnosis->main;
    }

    public function participant(): MainDiagnosisParticipant
    {
        return $this->main()->participantFor($this->user(), ParticipantRole::from($this->role));
    }

    public function isLeader(): bool
    {
        return $this->role === ParticipantRole::Leader->value;
    }

    public function steps(): StepList
    {
        $progress = app(StepProgress::class);

        return $this->isLeader()
            ? $progress->forLeader($this->main(), $this->user())
            : $progress->forPeer($this->main(), $this->user());
    }

    public function stepUrl(DiagnosisStep $step): string
    {
        return route('client.diagnosis.'.$step->slug(), [$this->organization, $this->diagnosis]);
    }

    public function overviewUrl(): string
    {
        return route('client.diagnosis.show', [$this->organization, $this->diagnosis]);
    }

    /**
     * A step opened again from the home (V10, T5): the leader's diagnosis runs,
     * or the peer has no step left. The arrow and "Tovább" then go home
     * instead of walking the steps again.
     */
    public function isRevisit(): bool
    {
        return $this->isLeader()
            ? ! $this->main()->stage->isBeforeRunning()
            : $this->steps()->current() === null;
    }

    /**
     * Where "Tovább" goes: the next step, or back to the overview after the last one or on a revisit.
     */
    public function nextUrl(DiagnosisStep $step): string
    {
        $next = $this->isRevisit() ? null : $this->steps()->after($step);

        return $next === null ? $this->overviewUrl() : $this->stepUrl($next->step);
    }

    /**
     * The header arrow: the previous step, or the overview from the first one or on a revisit.
     */
    public function previousUrl(DiagnosisStep $step): string
    {
        $previous = $this->isRevisit() ? null : $this->steps()->before($step);

        return $previous === null ? $this->overviewUrl() : $this->stepUrl($previous->step);
    }

    /**
     * The name on "Tovább": the next step's, as in the prototype ("A szervezet
     * értékei"); plain "Tovább" when it goes home.
     */
    public function nextLabel(DiagnosisStep $step): string
    {
        $next = $this->isRevisit() ? null : $this->steps()->after($step);

        return $next?->step->label() ?? __('diagnosis.next');
    }

    /**
     * The client layout's header for a step page: the step's symbol, its
     * question as the subtitle (prototype V2–V9, T3–T4).
     *
     * @return array<string, mixed>
     */
    public function stepLayout(DiagnosisStep $step, ?string $question = null): array
    {
        $steps = $this->steps();
        $revisit = $this->isRevisit();
        $previous = $revisit ? null : $steps->before($step);
        // The home's own label: "Diagnózis" on V10, "Kezdőlap" on T5.
        $home = match (true) {
            ! $revisit => __('diagnosis.overview_back'),
            $this->isLeader() => __('client.header.diagnosis'),
            default => __('diagnosis.home_back'),
        };

        return [
            'title' => $step->label(),
            'back' => $this->previousUrl($step),
            'backLabel' => $previous?->step->label() ?? $home,
            'step' => $steps->position($step).' / '.$steps->count(),
            'progress' => [$steps->position($step), $steps->count()],
            'symbol' => $step->symbol(peer: ! $this->isLeader()),
            'subtitle' => $question,
            'color' => 'cream',
        ];
    }

    public function markStep(DiagnosisStep $step, StepMark $mark): void
    {
        $this->main()->stepMarks()->updateOrCreate(
            ['user_id' => $this->user()->id, 'step' => $step],
            ['mark' => $mark],
        );
    }

    /**
     * Steps can be corrected until reading starts (23, question 3); after it only read.
     */
    public function acceptsInput(): bool
    {
        return $this->main()->acceptsInput();
    }

    /**
     * A peer works inside the diagnosis only after "Kezdem" with the required
     * consent, and not after withdrawing it (25 · 25.2, 25.6).
     */
    protected function ensurePeerStarted(): bool
    {
        if ($this->isLeader()) {
            return true;
        }

        $participant = $this->participant();

        return $participant->started_at !== null && ! $participant->hasWithdrawn();
    }

    protected function isImpersonating(): bool
    {
        return app(ImpersonationService::class)->isImpersonating();
    }

    protected function user(): User
    {
        return auth('web')->user();
    }

    protected function authorizeInput(): void
    {
        abort_unless($this->acceptsInput(), 403, __('diagnosis.locked'));
    }
}
