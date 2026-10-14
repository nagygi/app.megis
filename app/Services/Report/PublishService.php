<?php

namespace App\Services\Report;

use App\Enums\MainDiagnosisStage;
use App\Enums\MaterialContext;
use App\Exceptions\DiagnosisException;
use App\Mail\DiagnosisNoticeMail;
use App\Models\ChapterUpdate;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\ReportFeedback;
use App\Models\ReportSnapshot;
use App\Models\User;
use App\Services\Diagnoses\DiagnosisCalculator;
use App\Services\Diagnoses\ReadingService;
use App\Services\Diagnoses\ThresholdPolicy;
use App\Services\MainDiagnoses\StageMachine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * The 3rd gate (21): the checklist, publishing with the frozen snapshot and
 * the e-mails of the first publishing, corrections after it with the
 * "frissítve" marks, and the withdrawal with a reason.
 */
class PublishService
{
    public function __construct(
        private ReadingService $reading,
        private ThresholdPolicy $threshold,
        private ReportBuilder $reports,
        private TakeawayBuilder $takeaways,
        private StageMachine $stages,
        private DiagnosisCalculator $calculator,
    ) {}

    /**
     * Four rows; a missing one leads to its place (21 · 21.2). The threshold
     * row is not there without peers (21, question 5).
     *
     * @return array<string, bool>
     */
    public function checklist(MainDiagnosis $main): array
    {
        $progress = $this->reading->progress($main);

        return array_filter([
            'rows' => $progress['rows'] === $progress['rows_total'] && $progress['rows_total'] > 0,
            'claims' => $progress['claims'] === $progress['claims_total'] && $progress['texts'] === $progress['texts_total'] && $progress['gap'],
            'classified' => ! Material::query()
                ->where('parent_diagnosis_id', $main->diagnosis_id)
                ->where('context', MaterialContext::Occasion)
                ->whereNotNull('placed_at')
                ->whereNull('process_occasion_id')
                ->whereNull('classified_other_at')
                ->exists(),
            'threshold' => $main->hasPeers() ? ($this->threshold->isMet($main) || $this->threshold->isOverridden($main)) : null,
        ], fn ($value) => $value !== null);
    }

    public function canPublish(MainDiagnosis $main): bool
    {
        return $main->stage === MainDiagnosisStage::Reading && ! in_array(false, $this->checklist($main), true);
    }

    /**
     * "Publikálom" (21 · 21.3): the stage, the frozen report, the e-mails on the first time only.
     */
    public function publish(MainDiagnosis $main, User $admin): ReportSnapshot
    {
        if (! $this->canPublish($main)) {
            throw new DiagnosisException(__('admin.publish.not_ready'));
        }

        $first = ! ReportSnapshot::query()->where('main_diagnosis_id', $main->id)->exists();

        $snapshot = DB::transaction(function () use ($main, $admin, $first): ReportSnapshot {
            $this->stages->transition($main, MainDiagnosisStage::Published, $admin);

            return $this->snapshot($main->refresh(), $first ? ReportSnapshot::PUBLISHED : ReportSnapshot::REPUBLISHED, $admin);
        });

        // A republication after a withdrawal sends nothing (21 · 21.5).
        if ($first) {
            $this->notify($main->refresh());
        }

        return $snapshot;
    }

    /**
     * A correction after publishing (21 · 21.4): a new snapshot, and the
     * changed chapters marked "frissítve".
     */
    public function publishCorrection(MainDiagnosis $main, ?User $admin, string $reason = ChapterUpdate::CORRECTION, ?string $note = null): ReportSnapshot
    {
        if ($main->stage !== MainDiagnosisStage::Published) {
            throw new DiagnosisException(__('admin.publish.not_published'));
        }

        $previous = ReportSnapshot::query()->where('main_diagnosis_id', $main->id)->latest('sequence')->first();

        return DB::transaction(function () use ($main, $admin, $previous, $reason, $note): ReportSnapshot {
            $this->calculator->summarize($main);
            $snapshot = $this->snapshot($main->refresh(), ReportSnapshot::CORRECTION, $admin, $note);

            foreach (ReportBuilder::CHAPTERS as $chapter => $kind) {
                if (($previous?->data['chapters'][$chapter] ?? null) != ($snapshot->data['chapters'][$chapter] ?? null)
                    || ($chapter === 'brand' && ($previous?->data['texts']['brand_reading'] ?? null) !== ($snapshot->data['texts']['brand_reading'] ?? null))) {
                    // Feedback incorporated since the last snapshot marks the chapter "a ti visszajelzésetek alapján" (28 · 28.1).
                    $fromFeedback = ReportFeedback::query()
                        ->where('main_diagnosis_id', $main->id)
                        ->where('chapter', $chapter)
                        ->where('incorporated_at', '>=', $previous?->created_at ?? now()->subYears(10))
                        ->exists();

                    ChapterUpdate::create(['main_diagnosis_id' => $main->id, 'chapter' => $chapter, 'reason' => $fromFeedback ? ChapterUpdate::FEEDBACK : $reason, 'created_by' => $admin?->id]);
                }
            }

            return $snapshot;
        });
    }

    /**
     * "Visszavonom" (21 · 21.5): back to reading with the Draft status; the
     * report and the Útravaló disappear, the approvals stay.
     */
    public function withdraw(MainDiagnosis $main, User $admin, string $reason): void
    {
        if ($main->stage !== MainDiagnosisStage::Published) {
            throw new DiagnosisException(__('admin.publish.not_published'));
        }

        $this->stages->transition($main, MainDiagnosisStage::Reading, $admin, trim($reason));
    }

    private function snapshot(MainDiagnosis $main, string $reason, ?User $admin, ?string $note = null): ReportSnapshot
    {
        $sequence = (int) ReportSnapshot::query()->where('main_diagnosis_id', $main->id)->max('sequence') + 1;

        return ReportSnapshot::create([
            'main_diagnosis_id' => $main->id,
            'sequence' => $sequence,
            'reason' => $reason,
            'note' => $note,
            'data' => $this->reports->build($main),
            'created_by' => $admin?->id,
        ]);
    }

    /**
     * The leader's e-mail and the peers' Útravaló (21 · E-mailek).
     */
    private function notify(MainDiagnosis $main): void
    {
        $diagnosis = $main->diagnosis;
        $organization = $diagnosis->organization;

        Mail::to($main->leader)->send(new DiagnosisNoticeMail(
            __('mail.diagnosis_published.leader_subject', ['subject' => $main->subjectName()]),
            __('mail.diagnosis_published.leader_body', ['subject' => $main->subjectName()]),
            __('mail.diagnosis_published.leader_action'),
            route('client.report.show', [$organization, $diagnosis]),
            filled(config('megis.booking_url')) ? __('mail.diagnosis_published.booking_action') : null,
            config('megis.booking_url'),
        ));

        foreach ($this->takeaways->recipients($main) as $peer) {
            Mail::to($peer)->send(new DiagnosisNoticeMail(
                __('mail.diagnosis_published.peer_subject', ['subject' => $main->subjectName()]),
                __('mail.diagnosis_published.peer_body', ['subject' => $main->subjectName()]),
                __('mail.diagnosis_published.peer_action'),
                route('client.diagnosis.takeaway', [$organization, $diagnosis]),
            ));
        }
    }
}
