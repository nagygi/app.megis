<?php

namespace App\Services\Materials;

use App\Ai\AiContext;
use App\Enums\CapabilityMeasurementStatus;
use App\Enums\MaterialContext;
use App\Enums\MaterialStatus;
use App\Enums\MirrorStatus;
use App\Jobs\AnalyzeMaterial;
use App\Jobs\DetectCapabilities;
use App\Jobs\RecalculateMainDiagnosis;
use App\Jobs\TranscribeMaterial;
use App\Models\DiagnosisType;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\MirrorSession;
use App\Models\User;
use App\Services\Capabilities\CapabilityMeasurementPolicy;
use App\Services\Engine\AutomaticDiagnosisBuilder;
use App\Services\Engine\ValueCatalog;
use App\Services\Mirror\MirrorInsightService;
use Throwable;

/**
 * The way of a material: transcription when it is audio, speaker assignment
 * for admin uploads (06 · 6.3), measurement, then what each context does with
 * the result (06 · 6.1–6.3, 6.5).
 */
class MaterialPipeline
{
    public function __construct(private AutomaticDiagnosisBuilder $diagnoses) {}

    public function start(Material $material): void
    {
        if ($material->source->hasAudio() && blank($material->text)) {
            TranscribeMaterial::dispatch($material);

            return;
        }

        $this->transcribed($material);
    }

    /**
     * Admin uploads wait for the speakers to be assigned; everything else is measured.
     */
    public function transcribed(Material $material): void
    {
        // A dictation goes into a field as text; nothing is measured (08 · 8.1, 10 · 10.1).
        if ($material->context === MaterialContext::Dictation) {
            $material->update(['status' => MaterialStatus::Processed, 'processed_at' => now()]);

            return;
        }

        if ($material->context === MaterialContext::AdminUpload) {
            $material->update(['status' => MaterialStatus::AwaitingSpeakers]);

            return;
        }

        // A situation of the Képességek step is read for capabilities only, not for values (NM-12).
        if ($material->context === MaterialContext::CapabilityStory) {
            $material->update(['status' => MaterialStatus::Processed, 'processed_at' => now()]);
            $this->measureCapabilities($material);

            return;
        }

        $material->update(['status' => MaterialStatus::Pending]);
        AnalyzeMaterial::dispatch($material);
    }

    /**
     * @param  array<string, int|null>  $assignments  speaker => user id, or null to skip
     */
    public function assignSpeakers(Material $material, array $assignments): void
    {
        $material->update(['speaker_assignments' => $assignments, 'status' => MaterialStatus::Pending]);

        AnalyzeMaterial::dispatch($material);
    }

    public function analyzed(Material $material): void
    {
        match ($material->context) {
            MaterialContext::PublicMirror => $this->finishPublicMirror($material),
            MaterialContext::Mirror => $this->finishSignedInMirror($material),
            MaterialContext::AdminUpload => $this->buildUploadDiagnoses($material),
            MaterialContext::Dictation, MaterialContext::Occasion, MaterialContext::CapabilityStory => null,
        };

        // ET4 · the mégis Coach's suggestions, in a job of their own (NM-06).
        $session = $material->context->isMirror() && config('megis.mirror.deepening') ? $this->mirrorSession($material) : null;

        if ($session !== null) {
            app(MirrorInsightService::class)->start($session);
        }

        // A material of a main diagnosis recalculates it (13 · 13.4, 18 · 18.4).
        $this->recalculateParent($material);

        // Then its capabilities, in a job of their own (NM-12).
        $this->measureCapabilities($material);
    }

    /**
     * The capability measurement of a material, in a job of its own (NM-12),
     * when the switches let it; again from scratch when the text changed.
     */
    public function measureCapabilities(Material $material, bool $force = false): void
    {
        if (app(CapabilityMeasurementPolicy::class)->appliesTo($material)) {
            $material->update(['capability_status' => CapabilityMeasurementStatus::Pending]);
            DetectCapabilities::dispatch($material, $force);
        }
    }

    public function failed(Material $material, ?Throwable $exception): void
    {
        $material->update([
            'status' => MaterialStatus::Failed,
            'error' => $exception === null ? null : mb_substr(class_basename($exception).': '.$exception->getMessage(), 0, 1000),
        ]);

        $this->mirrorSession($material)?->update(['status' => MirrorStatus::Failed]);
    }

    /**
     * The values the engine matches to: for a material of a main diagnosis
     * the organization's values in its own words, from the recorded version (13 · 13.2).
     */
    public function catalogFor(Material $material): ValueCatalog
    {
        $snapshot = $this->mainOf($material)?->valueSetVersion?->snapshot;

        return $snapshot === null || $material->organization === null
            ? ValueCatalog::forOrganization($material->organization)
            : ValueCatalog::forDiagnosis($material->organization, $snapshot);
    }

    public function recalculateParent(Material $material): void
    {
        $main = $this->mainOf($material);

        if ($main !== null) {
            RecalculateMainDiagnosis::dispatch($main);
        }
    }

    private function mainOf(Material $material): ?MainDiagnosis
    {
        return $material->parent_diagnosis_id === null
            ? null
            : MainDiagnosis::query()->where('diagnosis_id', $material->parent_diagnosis_id)->first();
    }

    public function contextFor(Material $material): AiContext
    {
        return $material->organization === null
            ? AiContext::publicMirror($material)
            : AiContext::forOrganization($material->organization, $material);
    }

    /**
     * A mirror is one person; an admin upload is scored per assigned speaker.
     *
     * @return list<string|null>
     */
    public function speakersToScore(Material $material): array
    {
        if ($material->context !== MaterialContext::AdminUpload) {
            return [null];
        }

        $speakers = array_map('strval', array_keys(array_filter($material->speaker_assignments ?? [], fn ($userId) => $userId !== null)));

        // Attached to a main diagnosis, the whole conversation counts there too (18 · 18.4).
        return $material->parent_diagnosis_id === null ? $speakers : [...$speakers, null];
    }

    private function mirrorSession(Material $material): ?MirrorSession
    {
        return MirrorSession::query()->where('material_id', $material->id)->first();
    }

    /**
     * The e-mail comes before the mirror is read, so the account may be confirmed
     * while it is: then the diagnosis is built here, as claim() would (06 · 6.1).
     */
    private function finishPublicMirror(Material $material): void
    {
        $session = $this->mirrorSession($material);

        if ($session === null) {
            return;
        }

        if ($session->claimed_at === null) {
            // Only while unclaimed: a confirmation in the meantime has set its own status.
            MirrorSession::query()->whereKey($session->id)->whereNull('claimed_at')->update(['status' => MirrorStatus::Ready]);

            return;
        }

        if ($session->diagnosis_id === null && $material->user !== null && $material->organization !== null) {
            $diagnosis = $this->diagnoses->build(
                $material,
                $material->user,
                $material->organization,
                DiagnosisType::byKey(DiagnosisType::MIRROR),
                __('mirror.diagnosis_name', ['date' => $session->created_at->format('Y.m.d.')]),
                $session->created_at,
            );

            $session->update(['diagnosis_id' => $diagnosis->id]);
        }

        $session->update(['status' => MirrorStatus::Claimed]);
    }

    /**
     * The signed-in mirror becomes a diagnosis of the active organization at once (06 · 6.2).
     */
    private function finishSignedInMirror(Material $material): void
    {
        $session = $this->mirrorSession($material);

        if ($session !== null && $session->diagnosis_id === null && $material->user !== null && $material->organization !== null) {
            $diagnosis = $this->diagnoses->build(
                $material,
                $material->user,
                $material->organization,
                DiagnosisType::byKey(DiagnosisType::MIRROR),
                __('mirror.diagnosis_name', ['date' => now()->format('Y.m.d.')]),
                now(),
            );

            $session->update(['diagnosis_id' => $diagnosis->id]);
        }

        $session?->update(['status' => MirrorStatus::Ready]);
    }

    /**
     * One user diagnosis per assigned speaker, linked to the source file (06 · 6.3).
     */
    private function buildUploadDiagnoses(Material $material): void
    {
        foreach ($material->speaker_assignments ?? [] as $speaker => $userId) {
            $user = $userId === null ? null : User::query()->find($userId);

            if ($user === null || $material->organization === null) {
                continue;
            }

            $this->diagnoses->build(
                $material,
                $user,
                $material->organization,
                $material->diagnosis_type_id ? DiagnosisType::query()->findOrFail($material->diagnosis_type_id) : DiagnosisType::byKey(DiagnosisType::MIRROR),
                (string) ($material->original_name ?? __('mirror.diagnosis_name', ['date' => now()->format('Y.m.d.')])),
                $material->diagnosed_on ?? now(),
                (string) $speaker,
            );
        }
    }
}
