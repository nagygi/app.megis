<?php

namespace App\Livewire\Client\Diagnosis;

use App\Enums\AuditEventType;
use App\Enums\ConsentKind;
use App\Enums\MainDiagnosisStage;
use App\Enums\MaterialContext;
use App\Enums\MaterialStatus;
use App\Enums\ParticipantRole;
use App\Livewire\Client\Diagnosis\Concerns\InteractsWithMainDiagnosis;
use App\Models\Consent;
use App\Models\Diagnosis;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\Organization;
use App\Models\PersonalValueSet;
use App\Services\Audit\AuditLogger;
use App\Services\Capabilities\CapabilityOverview;
use App\Services\Capabilities\CapabilityStoryService;
use App\Services\Diagnoses\ThresholdPolicy;
use App\Services\MainDiagnoses\DiagnosisRunService;
use App\Services\MainDiagnoses\MainDiagnosisService;
use App\Services\MainDiagnoses\StepList;
use App\Services\MainDiagnoses\StepProgress;
use App\Services\Processes\OccasionService;
use App\Services\Report\ReportService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

/**
 * The entry of a main diagnosis. The leader sees "Mi vár rád" and later
 * "Innen folytatod" (V1, V1·b, 23 · 23.1, 23.2), then the home while it runs
 * and is read (V10, V10·b, 26 · 26.1, 26.2); a peer sees the invitation
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

        // T2 has its own header (peerWelcomeHeader); a Livewire update would keep the one of T1.
        $this->redirect($this->overviewUrl(), navigate: false);
    }

    /**
     * "Emlékeztető" on V10 (26, question 2): every peer who has not answered,
     * the 48-hour limit of 19 · 19.4; the leader does not see who got it.
     */
    public function remindPeers(DiagnosisRunService $run): void
    {
        abort_unless($this->isLeader() && $this->main()->stage === MainDiagnosisStage::Running && $this->main()->hasPeers(), 403);
        abort_if($this->isImpersonating(), 403, __('impersonation.forbidden'));

        $run->remindPeers($this->main(), $this->user());
        session()->flash('status', __('diagnosis.home.reminded'));
    }

    public function render(StepProgress $progress): View
    {
        $main = $this->main();
        $participant = $this->participant();
        $steps = $this->steps();
        $current = $steps->current();

        $snapshot = $this->isLeader() && $main->stage === MainDiagnosisStage::Published ? app(ReportService::class)->snapshot($main) : null;

        $view = match (true) {
            $snapshot !== null => 'livewire.client.diagnosis.overview-published',
            $this->isLeader() && in_array($main->stage, [MainDiagnosisStage::Running, MainDiagnosisStage::Reading], true) => 'livewire.client.diagnosis.overview-running',
            $this->isLeader() => 'livewire.client.diagnosis.overview-leader',
            $participant->started_at === null => 'livewire.client.diagnosis.invitation',
            default => 'livewire.client.diagnosis.overview-peer',
        };

        $canContribute = app(OccasionService::class)->canContribute($main, $this->user());
        // The coral plus: only while it runs, only on the homes (15 · 15.5).
        $plus = $canContribute ? route('client.diagnosis.contribute', [$this->organization, $this->diagnosis]) : null;

        $layout = match ($view) {
            'livewire.client.diagnosis.overview-published' => ['title' => __('diagnosis.home.title'), 'label' => __('client.header.diagnosis'), 'subtitle' => __('diagnosis.published_home.subtitle', ['product' => $main->product_name, 'date' => $main->published_at?->format('m.d.') ?? '–']), 'color' => 'charcoal'],
            'livewire.client.diagnosis.overview-running' => ['title' => __('diagnosis.home.title'), 'label' => __('client.header.diagnosis'), 'subtitle' => $this->runningSubtitle(), 'color' => 'charcoal', 'plus' => $plus],
            'livewire.client.diagnosis.invitation' => ['title' => __('diagnosis.invitation.title'), 'color' => 'coral'],
            'livewire.client.diagnosis.overview-peer' => $steps->current() === null
                ? ['title' => __('diagnosis.peer_home.title'), 'label' => __('client.header.home'), 'subtitle' => $this->peerHomeSubtitle(), 'color' => 'charcoal', 'plus' => $plus]
                : ['title' => __('diagnosis.what_awaits'), 'color' => 'cream', ...$this->peerWelcomeHeader($steps)],
            default => ['title' => __('diagnosis.what_awaits'), 'color' => 'cream', ...$this->leaderWelcomeHeader($steps)],
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
            'home' => $view === 'livewire.client.diagnosis.overview-running' ? $this->runningHome($main, $steps) : null,
            // V12 · the Képesség card from the approved rows (NM-12).
            'capabilities' => $view === 'livewire.client.diagnosis.overview-published' ? app(CapabilityOverview::class)->for($main, $this->user()) : null,
            'report' => $snapshot?->data,
            'reports' => $snapshot === null ? collect() : app(ReportService::class)->reportsFor($this->user(), $this->organization),
            // The Útravaló row lives from publishing (29 · 29.2).
            'takeawayReady' => $main->stage === MainDiagnosisStage::Published
                && isset(app(ReportService::class)->snapshot($main)?->data['takeaways'][$this->user()->id]),
            'situationCount' => Material::query()->where('parent_diagnosis_id', $main->diagnosis_id)->where('user_id', $this->user()->id)->where('context', MaterialContext::Occasion)->whereNotNull('placed_at')->count(),
        ])->layout('layouts::client', $layout)->title($layout['title']);
    }

    /**
     * The header of V1 and V1·b, as in the prototype (23 · 23.1, 23.2): "Első
     * belépés", the spiral, then "6 lépés, és indul a diagnózis." with
     * "kb. 13 perc · 03.02.-ig", or "Innen folytatod." with what is left.
     *
     * @return array{label?: string, symbol?: string, subtitle?: string, note?: string}
     */
    private function leaderWelcomeHeader(StepList $steps): array
    {
        $main = $this->main();

        if (! $main->stage->isBeforeRunning()) {
            return [];
        }

        $header = ['label' => __('client.header.first_visit'), 'symbol' => 'spiral'];

        if ($this->firstVisit || ! $steps->hasOwnProgress()) {
            $until = $main->starts_on ? __('diagnosis.until', ['date' => $main->starts_on->format('m.d.')]) : __('diagnosis.date_open');

            return $header + [
                'subtitle' => trans_choice($main->isSnapshot() ? 'diagnosis.steps_lead_snapshot' : 'diagnosis.steps_lead', $steps->count(), ['count' => $steps->count()]),
                // The snapshot says which one it is, as V1·c does.
                'note' => ($main->isSnapshot() ? $main->product_name.' · ' : '').__('diagnosis.minutes_until', ['minutes' => $steps->totalMinutes()]).' '.$until,
            ];
        }

        return $header + [
            'subtitle' => __('diagnosis.continue_lead'),
            'note' => __('diagnosis.continue_count', ['done' => $steps->doneCount(), 'count' => $steps->count(), 'minutes' => $steps->remainingMinutes()]),
        ];
    }

    /**
     * The header of T2, the pair of V1 (25 · 25.3): "Két lépés most, egy, amíg fut." · "kb. 5 perc".
     *
     * @return array{label?: string, symbol?: string, subtitle?: string, note?: string}
     */
    private function peerWelcomeHeader(StepList $steps): array
    {
        if ($this->participant()->hasWithdrawn() || ! $this->main()->acceptsInput()) {
            return [];
        }

        return [
            'label' => __('client.header.first_visit'),
            'symbol' => 'spiral',
            'subtitle' => trans_choice('diagnosis.peer_steps_lead', $steps->count(), ['count' => $steps->count()]),
            'note' => __('diagnosis.minutes_about', ['minutes' => $steps->totalMinutes()]),
        ];
    }

    /**
     * "{Termék} · fut · {kezdés} → {publikálás}" (26 · 26.1).
     */
    private function runningSubtitle(): string
    {
        $main = $this->main();

        return __('diagnosis.home.subtitle', [
            'product' => $main->product_name,
            'stage' => mb_strtolower($main->stage->label()),
            'from' => ($main->running_at ?? $main->starts_on)?->format('m.d.') ?? '–',
            'to' => $main->publishes_on->format('m.d.'),
        ]);
    }

    /**
     * V10 · "Hol tartunk" (26 · 26.1): the weeks (days in a snapshot), the
     * steps of the run, numbers and never answers.
     *
     * @return array<string, mixed>
     */
    private function runningHome(MainDiagnosis $main, StepList $steps): array
    {
        $start = ($main->running_at ?? $main->starts_on ?? now())->copy()->startOfDay();
        $unit = $main->isSnapshot() ? 1 : 7;
        $count = max(1, (int) ceil(($start->diffInDays($main->publishes_on) + 1) / $unit));
        $current = min($count, max(1, (int) floor($start->diffInDays(now()) / $unit) + 1));
        $threshold = app(ThresholdPolicy::class);
        $materials = Material::query()->where('parent_diagnosis_id', $main->diagnosis_id)->where('status', '!=', MaterialStatus::Failed)->get();
        $conversations = $main->conversations()->with('admin')->orderBy('held_at')->get();
        $next = $conversations->first(fn ($conversation) => $conversation->held_at->isFuture());
        $peers = $main->peers()->count();
        $reading = $main->stage === MainDiagnosisStage::Reading;

        return [
            'unit' => $main->isSnapshot() ? 'day' : 'week',
            'segments' => collect(range(1, $count))->map(fn (int $index) => $index < $current ? 'done' : ($index === $current ? 'now' : 'later'))->all(),
            'current' => $current,
            'count' => $count,
            // While it runs: the points to be measured; from the reading on the measured
            // ones, the same number as on the admin Kert and the report (R0).
            'measuringPoints' => $reading ? (int) ($main->calculation['measuring_points'] ?? 0) : $main->diagnosis->rows()->count(),
            'onboarding' => ['done' => $steps->current() === null, 'date' => $main->running_at],
            'peers' => $main->hasPeers() ? [
                'answered' => $threshold->peersAnswered($main),
                'total' => $peers,
                'missing' => $threshold->missingPeers($main),
            ] : null,
            'conversations' => ['held' => $conversations->filter(fn ($conversation) => $conversation->held_at->isPast())->count(), 'next' => $next],
            'contributions' => [
                'recordings' => $materials->filter(fn (Material $material) => ! $material->is_situation && $material->source->hasAudio())->count(),
                'documents' => $materials->filter(fn (Material $material) => ! $material->is_situation && ! $material->source->hasAudio() && ! in_array($material->context, [MaterialContext::Dictation, MaterialContext::CapabilityStory], true))->count(),
                'situations' => $materials->where('is_situation', true)->count(),
            ],
            // Képességek: the situations told, the peers' among them; numbers only (NM-12).
            'capabilities' => $main->measuresCapabilities() ? app(CapabilityStoryService::class)->counts($main) : null,
            'reading' => ['state' => $reading ? 'now' : 'later', 'from' => $main->readingStartsOn(), 'to' => $main->publishes_on->copy()->subDay()],
            // Withdrawn after publishing, the mégis corrects it (21 · 21.5).
            'correcting' => $reading && $main->published_at !== null,
        ];
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
