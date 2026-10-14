<?php

namespace App\Services\Processes;

use App\Enums\InboxItemType;
use App\Enums\MainDiagnosisStage;
use App\Enums\MaterialContext;
use App\Enums\MaterialSource;
use App\Enums\MaterialStatus;
use App\Enums\ParticipantRole;
use App\Enums\ProcessKind;
use App\Exceptions\UnreadableMaterialException;
use App\Jobs\RecalculateMainDiagnosis;
use App\Models\MainDiagnosis;
use App\Models\Material;
use App\Models\OccasionExperience;
use App\Models\Process;
use App\Models\ProcessOccasion;
use App\Models\User;
use App\Services\Inbox\InboxService;
use App\Services\Materials\MaterialPipeline;
use App\Services\Materials\MaterialStore;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The occasions of a running diagnosis (12): an upload or a situation,
 * "Hova tartozik?", the occasion it belongs to, and the experience. The
 * material is processed once it is placed; "Egyikhez sem" goes to the admin.
 */
class OccasionService
{
    public function __construct(
        private MaterialStore $materials,
        private MaterialPipeline $pipeline,
        private InboxService $inbox,
    ) {}

    /**
     * An audio file or a text file (12 · 12.1).
     *
     * @throws UnreadableMaterialException
     */
    public function upload(MainDiagnosis $main, User $user, UploadedFile $file): Material
    {
        $organization = $main->diagnosis->organization;
        $isAudio = in_array(strtolower($file->getClientOriginalExtension()), config('megis.materials.audio_extensions'), true);

        $material = $isAudio
            ? $this->materials->fromAudio($file, MaterialContext::Occasion, MaterialSource::AudioUpload, $user, $organization)
            : $this->materials->fromTextFile($file, MaterialContext::Occasion, $user, $organization);

        if ($isAudio) {
            $this->materials->ensureAudioLength($material);
        }

        $material->update(['parent_diagnosis_id' => $main->diagnosis_id, 'created_by' => $user->id]);

        return $material;
    }

    /**
     * "+ Anyag": the admin adds to an occasion with the same rules (12 · 12.6),
     * also during reading and after publishing (13 · 13.4, 15, question 6).
     *
     * @throws UnreadableMaterialException
     */
    public function adminUpload(MainDiagnosis $main, Process $process, string $date, UploadedFile $file, User $admin, bool $recordingConsent): Material
    {
        $organization = $main->diagnosis->organization;
        $isAudio = in_array(strtolower($file->getClientOriginalExtension()), config('megis.materials.audio_extensions'), true);

        $material = $isAudio
            ? $this->materials->fromAudio($file, MaterialContext::Occasion, MaterialSource::AudioUpload, null, $organization)
            : $this->materials->fromTextFile($file, MaterialContext::Occasion, null, $organization);

        if ($isAudio) {
            $this->materials->ensureAudioLength($material);
        }

        $material->update(['parent_diagnosis_id' => $main->diagnosis_id, 'created_by' => $admin->id]);

        return $this->place($material, $process, $date, $admin, $recordingConsent);
    }

    /**
     * The diagnosis an admin upload belongs to: the organization's latest one
     * that runs, is read or is published.
     */
    public function adminDiagnosisFor(Process $process): ?MainDiagnosis
    {
        return MainDiagnosis::query()
            ->inStages(MainDiagnosisStage::Running, MainDiagnosisStage::Reading, MainDiagnosisStage::Published)
            ->whereHas('diagnosis', fn ($query) => $query->where('organization_id', $process->organization_id))
            ->latest('id')
            ->first();
    }

    /**
     * "Egy helyzet": the user's own account, typed (12 · 12.3).
     */
    public function situationText(MainDiagnosis $main, User $user, string $text): Material
    {
        $material = $this->materials->fromText($text, MaterialContext::Occasion, $user, $main->diagnosis->organization);
        $material->update(['parent_diagnosis_id' => $main->diagnosis_id, 'is_situation' => true]);

        return $material;
    }

    /**
     * "Egy helyzet", spoken with the recorder (12 · 12.3).
     */
    public function situationRecording(MainDiagnosis $main, User $user, UploadedFile $recording, int $seconds): Material
    {
        $material = $this->materials->fromAudio($recording, MaterialContext::Occasion, MaterialSource::Recording, $user, $main->diagnosis->organization);
        $material->update([
            'parent_diagnosis_id' => $main->diagnosis_id,
            'is_situation' => true,
            'audio_seconds' => $material->audio_seconds ?? max(0, min($seconds, (int) config('megis.materials.recording_max_seconds'))),
        ]);

        return $material;
    }

    /**
     * The measured processes the user may choose (12 · 12.2): every one for the
     * leader, the ones they take part in for a peer.
     *
     * @return Collection<int, Process>
     */
    public function eligibleProcesses(MainDiagnosis $main, User $user): Collection
    {
        $processes = $main->processes()->get();

        if ($processes->isEmpty()) {
            $processes = Process::query()->whereBelongsTo($main->diagnosis->organization)->measured()->get();
        }

        if ($main->isLeader($user)) {
            return $processes->sortBy('name')->values();
        }

        return $processes
            ->filter(fn (Process $process) => $process->participants()->where('user_id', $user->id)->exists())
            ->sortBy('name')
            ->values();
    }

    /**
     * "nem mérjük" processes are shown, dashed, but cannot be chosen (12 · 12.2).
     *
     * @return Collection<int, Process>
     */
    public function excludedProcesses(MainDiagnosis $main): Collection
    {
        return Process::query()
            ->whereBelongsTo($main->diagnosis->organization)
            ->whereNull('parent_id')
            ->where('kind', ProcessKind::Excluded)
            ->orderBy('name')
            ->get();
    }

    /**
     * The latest calendar occasion of the eligible processes, offered first (12 · 12.2).
     *
     * @param  Collection<int, Process>  $processes
     */
    public function latestCalendarOccasion(Collection $processes): ?ProcessOccasion
    {
        return ProcessOccasion::query()
            ->whereIn('process_id', $processes->pluck('id'))
            ->where('source', 'calendar')
            ->where('occurred_at', '<=', now())
            ->latest('occurred_at')
            ->first();
    }

    /**
     * "Hova tartozik?": the occasion of the process on that day, or a new one;
     * without a process the admin classifies it (12 · 12.2, 12.6). Processing starts.
     */
    public function place(Material $material, ?Process $process, string $date, User $user, bool $recordingConsent): Material
    {
        DB::transaction(function () use ($material, $process, $date, $user, $recordingConsent): void {
            $occasion = $process === null ? null : $this->occasionFor($process, $date, $user);

            $material->update([
                'process_occasion_id' => $occasion?->id,
                'placed_at' => now(),
                ...($recordingConsent ? ['recording_consent_at' => now(), 'recording_consent_by' => $user->id] : []),
            ]);

            if ($occasion === null) {
                $this->inbox->open(
                    InboxItemType::Classification,
                    __('processes.occasions.classification_summary', ['name' => $user->name, 'file' => $material->original_name ?? $material->context->label()]),
                    diagnosis: $material->parentDiagnosis,
                    subject: $material,
                    dedupeKey: 'classification:'.$material->id,
                );
            }
        });

        if ($material->status === MaterialStatus::Pending && $material->processed_at === null) {
            $this->pipeline->start($material->refresh());
        }

        return $material;
    }

    /**
     * The admin places an "Egyikhez sem" material, in the Beérkező or on the tab (12 · 12.6, 22).
     */
    public function classify(Material $material, Process $process, string $date, User $admin): void
    {
        $occasion = $this->occasionFor($process, $date, $admin);
        $material->update(['process_occasion_id' => $occasion->id, 'classified_other_at' => null]);

        $this->inbox->closeAutomatically('classification:'.$material->id);
        $this->pipeline->recalculateParent($material);
    }

    /**
     * "Egyéb": processed and kept as a trace, no process diagnosis (13 · 13.2).
     */
    public function markOther(Material $material): void
    {
        $material->update(['classified_other_at' => now()]);
        $this->inbox->closeAutomatically('classification:'.$material->id);
    }

    /**
     * "Újra" after a failed upload (12 · 12.1).
     */
    public function retry(Material $material): void
    {
        $material->update(['status' => MaterialStatus::Pending, 'error' => null]);
        $this->pipeline->start($material);
    }

    /**
     * "Kiveszem": the material goes with its audio (12 · 12.1).
     */
    public function remove(Material $material): void
    {
        $this->inbox->closeAutomatically('classification:'.$material->id);
        $this->materials->delete($material);
        $this->pipeline->recalculateParent($material);
    }

    /**
     * "A megélésed" (12 · 12.4): sent, or skipped and made up later.
     *
     * @param  array<string, string>|null  $answers
     */
    public function experience(Material $material, User $user, ?array $answers, ?string $effectiveness, ?string $note, bool $skip): OccasionExperience
    {
        $main = MainDiagnosis::query()->where('diagnosis_id', $material->parent_diagnosis_id)->firstOrFail();

        $experience = OccasionExperience::query()->updateOrCreate(
            ['process_occasion_id' => $material->process_occasion_id, 'user_id' => $user->id],
            [
                'main_diagnosis_id' => $main->id,
                'material_id' => $material->id,
                'value_set_version_id' => $main->value_set_version_id,
                ...($skip
                    ? ['skipped_at' => now()]
                    : ['answers' => $answers, 'effectiveness' => $effectiveness, 'note' => filled($note) ? trim($note) : null, 'submitted_at' => now(), 'skipped_at' => null]),
            ],
        );

        if (! $skip) {
            RecalculateMainDiagnosis::dispatch($main);
        }

        return $experience;
    }

    /**
     * Who may add to the diagnosis: the leader and the peers who started,
     * while it takes input (12, 25 · 25.5).
     */
    public function canContribute(MainDiagnosis $main, User $user): bool
    {
        if (! $main->acceptsInput() || $main->stage->isBeforeRunning()) {
            return false;
        }

        if ($main->isLeader($user)) {
            return true;
        }

        $peer = $main->participantFor($user, ParticipantRole::Peer);

        return $peer !== null && $peer->started_at !== null && ! $peer->hasWithdrawn();
    }

    private function occasionFor(Process $process, string $date, User $user): ProcessOccasion
    {
        $day = CarbonImmutable::parse($date);

        return ProcessOccasion::query()
            ->where('process_id', $process->id)
            ->whereDate('occurred_at', $day->toDateString())
            ->first()
            ?? ProcessOccasion::create([
                'process_id' => $process->id,
                'occurred_at' => $day->setTime(12, 0),
                'source' => 'manual',
                'created_by' => $user->id,
            ]);
    }
}
