<?php

namespace App\Services\Materials;

use App\Ai\AiContext;
use App\Enums\MaterialContext;
use App\Enums\MaterialStatus;
use App\Enums\MirrorStatus;
use App\Jobs\AnalyzeMaterial;
use App\Jobs\TranscribeMaterial;
use App\Models\DiagnosisType;
use App\Models\Material;
use App\Models\MirrorSession;
use App\Models\User;
use App\Services\Engine\AutomaticDiagnosisBuilder;
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
        if ($material->context === MaterialContext::AdminUpload) {
            $material->update(['status' => MaterialStatus::AwaitingSpeakers]);

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
            MaterialContext::PublicMirror => $this->mirrorSession($material)?->update(['status' => MirrorStatus::Ready]),
            MaterialContext::Mirror => $this->finishSignedInMirror($material),
            MaterialContext::AdminUpload => $this->buildUploadDiagnoses($material),
        };
    }

    public function failed(Material $material, ?Throwable $exception): void
    {
        $material->update([
            'status' => MaterialStatus::Failed,
            'error' => $exception === null ? null : mb_substr(class_basename($exception).': '.$exception->getMessage(), 0, 1000),
        ]);

        $this->mirrorSession($material)?->update(['status' => MirrorStatus::Failed]);
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

        return array_map('strval', array_keys(array_filter($material->speaker_assignments ?? [], fn ($userId) => $userId !== null)));
    }

    private function mirrorSession(Material $material): ?MirrorSession
    {
        return MirrorSession::query()->where('material_id', $material->id)->first();
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
