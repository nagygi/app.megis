<?php

namespace App\Livewire\Client\Diagnosis;

use App\Enums\AuditEventType;
use App\Enums\ConsentKind;
use App\Enums\MainDiagnosisStage;
use App\Enums\ParticipantRole;
use App\Livewire\Client\Diagnosis\Concerns\InteractsWithMainDiagnosis;
use App\Models\Consent;
use App\Models\Diagnosis;
use App\Models\MainDiagnosis;
use App\Models\Organization;
use App\Models\PersonalValueSet;
use App\Services\Audit\AuditLogger;
use App\Services\MainDiagnoses\MainDiagnosisService;
use App\Services\MainDiagnoses\StepProgress;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

/**
 * The entry of a main diagnosis. The leader sees "Mi vár rád" and later
 * "Innen folytatod" (V1, V1·b, 23 · 23.1, 23.2); a peer sees the invitation
 * with the consents (T1), then "Mi vár rád" (T2) and their home (T5, 25).
 */
class Overview extends Component
{
    use InteractsWithMainDiagnosis;

    public bool $answersConsent = false;

    public bool $quotesConsent = false;

    /**
     * V1 on the first entry, V1·b ("Innen folytatod") after it (23 · 23.1, 23.2).
     */
    public bool $firstVisit = false;

    public function mount(Organization $organization, Diagnosis $diagnosis, MainDiagnosisService $mainDiagnoses): void
    {
        $this->bootDiagnosis($organization, $diagnosis);
        $this->firstVisit = $this->participant()->opened_at === null;

        // "Megnyitotta" is the first signed-in view, never one in the user's name (plan 3.2).
        if (! $this->isImpersonating()) {
            $mainDiagnoses->opened($this->main(), $this->participant());
        }
    }

    /**
     * T1 "Kezdem": the required consent and, if the admin turned quotes on,
     * the optional one; who, when and which text is recorded (25 · 25.2).
     */
    public function start(AuditLogger $audit): void
    {
        abort_if($this->isLeader(), 404);
        // Consent is the user's own act, never in their name (14 · 14.6).
        abort_if($this->isImpersonating(), 403, __('impersonation.forbidden'));
        $this->authorizeInput();

        $this->validate(
            ['answersConsent' => ['accepted']],
            ['answersConsent.accepted' => __('diagnosis.consent.required')],
        );

        $main = $this->main();
        $participant = $this->participant();
        $user = $this->user();

        DB::transaction(function () use ($main, $participant, $user, $audit): void {
            $kinds = [ConsentKind::Answers, ...($main->show_peer_quotes && $this->quotesConsent ? [ConsentKind::Quotes] : [])];

            foreach ($kinds as $kind) {
                Consent::create([
                    'user_id' => $user->id,
                    'main_diagnosis_id' => $main->id,
                    'kind' => $kind,
                    'text_version' => __('diagnosis.consent.version'),
                    'text' => $this->consentText($kind),
                    'given_at' => now(),
                ]);

                $audit->record(AuditEventType::ConsentGiven, $user, $user, ['diagnosis' => $this->diagnosis->name, 'kind' => $kind->value]);
            }

            $participant->update(['started_at' => now()]);
        });
    }

    public function render(StepProgress $progress): View
    {
        $main = $this->main();
        $participant = $this->participant();
        $steps = $this->steps();
        $current = $steps->current();

        $view = match (true) {
            $this->isLeader() => 'livewire.client.diagnosis.overview-leader',
            $participant->started_at === null => 'livewire.client.diagnosis.invitation',
            default => 'livewire.client.diagnosis.overview-peer',
        };

        $layout = match ($view) {
            'livewire.client.diagnosis.invitation' => ['title' => __('diagnosis.invitation.title'), 'color' => 'coral'],
            'livewire.client.diagnosis.overview-peer' => $steps->current() === null
                ? ['title' => __('diagnosis.peer_home.title'), 'subtitle' => $this->peerHomeSubtitle(), 'color' => 'charcoal']
                : ['title' => __('diagnosis.what_awaits'), 'color' => 'cream'],
            default => ['title' => __('diagnosis.what_awaits'), 'color' => 'cream'],
        };

        return view($view, [
            'main' => $main,
            'participant' => $participant,
            'steps' => $steps,
            'current' => $current,
            'stepUrl' => fn ($step) => $this->stepUrl($step),
            'withdrawn' => $participant->hasWithdrawn(),
            'ownValues' => PersonalValueSet::query()->where('user_id', $this->user()->id)->first()?->items()->pluck('name') ?? collect(),
            'experience' => $progress->experienceDiagnosis($main, $this->user()),
            'leaderName' => $main->leader->name,
            'otherRoles' => $this->otherRoles(),
        ])->layout('layouts::client', $layout)->title($layout['title']);
    }

    /**
     * "Value · Core Team · Teljes · fut", "3. hét a 7-ből · publikálás: 04.18." (T5, 25 · 25.5).
     */
    private function peerHomeSubtitle(): string
    {
        $main = $this->main();
        $start = $main->running_at ?? now();
        $weeks = max(1, (int) ceil($start->diffInDays($main->publishes_on) / 7));
        $week = min($weeks, max(1, (int) floor($start->diffInDays(now()) / 7) + 1));

        return __('diagnosis.peer_home.subtitle', [
            'subject' => $main->subjectName(),
            'product' => $main->product_name,
            'stage' => $main->stage->label(),
            'week' => $week,
            'weeks' => $weeks,
            'date' => $main->publishes_on->format('m.d.'),
        ]);
    }

    /**
     * The user's diagnoses with the other role in this organization, so the
     * leader's page links to the peer view and back (26 · 26.4).
     *
     * @return Collection<int, MainDiagnosis>
     */
    private function otherRoles(): Collection
    {
        return MainDiagnosis::query()
            ->withParticipant($this->user(), $this->isLeader() ? ParticipantRole::Peer : ParticipantRole::Leader)
            ->whereKeyNot($this->main()->id)
            ->whereNotIn('stage', $this->isLeader()
                ? [MainDiagnosisStage::Draft->value, MainDiagnosisStage::Invited->value, MainDiagnosisStage::Filling->value, MainDiagnosisStage::Closed->value]
                : [MainDiagnosisStage::Draft->value, MainDiagnosisStage::Closed->value])
            ->whereHas('diagnosis', fn ($query) => $query->where('organization_id', $this->organization->id))
            ->with('diagnosis')
            ->get();
    }

    private function consentText(ConsentKind $kind): string
    {
        return __('diagnosis.consent.'.$kind->value, ['leader' => $this->main()->leader->name]);
    }
}
