<?php

namespace App\Services\MainDiagnoses;

use App\Enums\AuditEventType;
use App\Enums\ConsentKind;
use App\Enums\MainDiagnosisStage;
use App\Exceptions\DiagnosisException;
use App\Models\Consent;
use App\Models\Diagnosis;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Withdrawing a peer's consent until publishing (25 · 25.6, 31 · 31.6).
 * Without the required one the peer's answers and situations leave the
 * diagnosis; without the quotes one only the quotes stay hidden.
 */
class ConsentService
{
    public function __construct(private AuditLogger $audit) {}

    public function canRevoke(Consent $consent): bool
    {
        return $consent->revoked_at === null
            && ! in_array($consent->mainDiagnosis->stage, [MainDiagnosisStage::Published, MainDiagnosisStage::Closed], true);
    }

    public function revoke(Consent $consent, User $user): void
    {
        if ($consent->user_id !== $user->id || ! $this->canRevoke($consent)) {
            throw new DiagnosisException(__('client.consents.cannot_revoke'));
        }

        DB::transaction(function () use ($consent, $user): void {
            $main = $consent->mainDiagnosis;
            $kinds = $consent->kind === ConsentKind::Answers ? [ConsentKind::Answers, ConsentKind::Quotes] : [ConsentKind::Quotes];

            Consent::query()
                ->where('user_id', $user->id)
                ->where('main_diagnosis_id', $main->id)
                ->whereIn('kind', $kinds)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            // The answers leave the calculation; the leader only sees the count (25 · 25.6, 18 · 18.6).
            if ($consent->kind === ConsentKind::Answers) {
                Diagnosis::query()
                    ->where('parent_id', $main->diagnosis_id)
                    ->where('user_id', $user->id)
                    ->whereNull('excluded_at')
                    ->update(['excluded_at' => now()]);
            }

            $this->audit->record(AuditEventType::ConsentRevoked, $user, $user, [
                'diagnosis' => $main->diagnosis->name,
                'kind' => $consent->kind->value,
            ]);
        });
    }
}
