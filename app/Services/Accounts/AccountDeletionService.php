<?php

namespace App\Services\Accounts;

use App\Enums\AuditEventType;
use App\Enums\DiagnosisLevel;
use App\Enums\InboxItemType;
use App\Enums\MainDiagnosisStage;
use App\Enums\OrganizationType;
use App\Jobs\RecalculateMainDiagnosis;
use App\Mail\AccountDeletedMail;
use App\Models\AuditEvent;
use App\Models\Diagnosis;
use App\Models\DiagnosisClaim;
use App\Models\EvidenceTrace;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\MirrorSession;
use App\Models\OccasionExperience;
use App\Models\Organization;
use App\Models\PersonalValueSet;
use App\Models\ProcessParticipant;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Inbox\InboxService;
use App\Services\Materials\MaterialStore;
use App\Services\Report\PublishService;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * "Adataim törlése" (31 · 31.7, plan 3.7). The account goes inactive at once;
 * within 24 hours the personal data is removed and the user row stays as a
 * tombstone. The published numbers remain, the quotes leave the report with
 * a "frissítve" mark; a claim left without a trace opens a Beérkező row.
 */
class AccountDeletionService
{
    public function __construct(
        private MaterialStore $materials,
        private AuditLogger $audit,
        private InboxService $inbox,
    ) {}

    /**
     * Inactive and signed out at once; the purge follows (`megis:accounts:purge`).
     */
    public function request(User $user): void
    {
        $user->forceFill(['deletion_requested_at' => now(), 'deactivated_at' => $user->deactivated_at ?? now()])->save();
        $user->invalidateSessions();

        $this->audit->record(AuditEventType::AccountDeletionRequested, $user, $user);
    }

    public function purge(User $user): void
    {
        if ($user->anonymized_at !== null) {
            return;
        }

        $email = $user->email;
        $materials = Material::query()->where('user_id', $user->id)->get();
        $affected = $this->affectedDiagnoses($user, $materials);

        DB::transaction(function () use ($user, $materials): void {
            // The quotes and traces of the person: theirs, or from their materials.
            EvidenceTrace::query()
                ->where(fn ($query) => $query->where('user_id', $user->id)->orWhereIn('material_id', $materials->pluck('id')))
                ->delete();

            foreach ($materials as $material) {
                $this->materials->delete($material);
            }

            OccasionExperience::query()->where('user_id', $user->id)->delete();
            // Their own diagnoses: Megélés, Értéktükör and user level ones (04, 17 · 17.4).
            Diagnosis::query()->where('level', DiagnosisLevel::User)->where('user_id', $user->id)->get()->each->delete();
            MirrorSession::query()->where('user_id', $user->id)->delete();
            PersonalValueSet::query()->where('user_id', $user->id)->get()->each(function (PersonalValueSet $set): void {
                $set->items()->delete();
                $set->delete();
            });
            ProcessParticipant::query()->where('user_id', $user->id)->delete();

            // A private person's organization is one person: it goes too (31, question 1).
            Organization::query()
                ->where('type', OrganizationType::Individual)
                ->whereHas('memberships', fn ($query) => $query->where('user_id', $user->id))
                ->get()
                ->each(function (Organization $organization): void {
                    // Diagnoses keep their organization; the subdiagnoses go before their parents.
                    Diagnosis::query()->where('organization_id', $organization->id)->whereNotNull('parent_id')->get()->each->delete();
                    Diagnosis::query()->where('organization_id', $organization->id)->get()->each->delete();
                    $organization->delete();
                });

            $user->forceFill([
                'first_name' => null,
                'last_name' => null,
                'email' => 'deleted-'.$user->id.'@deleted.invalid',
                'password' => null,
                'anonymized_at' => now(),
            ])->save();

            // The log lives as long as its organization: a private person's rows went with it
            // above, and whoever has no organization left leaves no log behind (33 · 33.7).
            if (! $user->memberships()->exists()) {
                AuditEvent::query()->where('subject_user_id', $user->id)->delete();
            } else {
                // The rest stays without personal data: the e-mails and the names that may hold one.
                AuditEvent::query()
                    ->where('subject_user_id', $user->id)
                    ->whereNotNull('payload')
                    ->get()
                    ->each(fn (AuditEvent $event) => $event->forceFill([
                        'payload' => $event->type === AuditEventType::EmailChanged ? null : (Arr::except($event->payload, ['organization', 'diagnosis']) ?: null),
                    ])->save());

                $this->audit->record(AuditEventType::AccountDeleted, $user, null);
            }
        });

        $this->afterPurge($affected);

        Mail::to($email)->send(new AccountDeletedMail);
    }

    /**
     * The main diagnoses the person took part in or added to.
     *
     * @param  Collection<int, Material>  $materials
     * @return Collection<int, MainDiagnosis>
     */
    private function affectedDiagnoses(User $user, Collection $materials): Collection
    {
        $diagnosisIds = $materials->pluck('parent_diagnosis_id')->filter()
            ->merge(EvidenceTrace::query()->where('user_id', $user->id)->join('diagnosis_rows', 'diagnosis_rows.id', '=', 'evidence_traces.diagnosis_row_id')->pluck('diagnosis_rows.diagnosis_id'))
            ->merge(Diagnosis::query()->where('user_id', $user->id)->whereNotNull('parent_id')->pluck('parent_id'))
            ->unique();

        return MainDiagnosis::query()
            ->whereIn('diagnosis_id', $diagnosisIds)
            ->orWhereHas('participants', fn ($query) => $query->where('user_id', $user->id))
            ->get();
    }

    /**
     * Running ones are calculated again; published ones get a correction
     * without the quotes; a claim without a trace goes to the Beérkező.
     *
     * @param  Collection<int, MainDiagnosis>  $mains
     */
    private function afterPurge(Collection $mains): void
    {
        foreach ($mains as $main) {
            if ($main->stage === MainDiagnosisStage::Published) {
                app(PublishService::class)->publishCorrection($main, null);
            } elseif ($main->stage !== MainDiagnosisStage::Closed) {
                RecalculateMainDiagnosis::dispatch($main);
            }

            $orphans = DiagnosisClaim::query()->where('diagnosis_id', $main->diagnosis_id)->get()
                ->filter(fn (DiagnosisClaim $claim) => EvidenceTrace::query()->whereIn('id', $claim->trace_ids ?? [])->doesntExist());

            foreach ($orphans as $claim) {
                $this->inbox->open(
                    InboxItemType::ProcessingError,
                    __('admin.inbox.rows.claim_without_trace', ['claim' => mb_substr($claim->text, 0, 120)]),
                    diagnosis: $main->diagnosis,
                    subject: $claim,
                    dedupeKey: 'claim-without-trace:'.$claim->id,
                );
            }
        }
    }
}
