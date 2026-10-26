<?php

namespace App\Livewire\Concerns;

use App\Enums\MaterialContext;
use App\Enums\MaterialSource;
use App\Enums\MaterialStatus;
use App\Models\Material;
use App\Models\Organization;
use App\Models\User;
use App\Services\Materials\MaterialPipeline;
use App\Services\Materials\MaterialStore;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * A text field can be spoken instead of typed (08 · 8.1, 10 · 10.1, 24): the
 * recording is transcribed and the transcript goes into the field, editable.
 * The recording itself is kept like every recording (06 · 6.6). The
 * component uses WithFileUploads and says which fields can be dictated.
 */
trait WithDictation
{
    /** @var TemporaryUploadedFile|null */
    public $dictationAudio = null;

    /**
     * Fields waiting for their transcript: field => material id.
     *
     * @var array<string, int>
     */
    public array $dictations = [];

    public function dictate(string $field, int $seconds, MaterialStore $materials, MaterialPipeline $pipeline): void
    {
        abort_unless($this->canDictate($field), 403);

        $this->validate([
            'dictationAudio' => [
                'required',
                'file',
                'max:20480',
                'mimetypes:audio/webm,video/webm,audio/ogg,audio/mp4,video/mp4,audio/x-m4a,audio/mpeg,audio/wav,audio/x-wav',
            ],
        ], attributes: ['dictationAudio' => __('diagnosis.dictation.label')]);

        $material = $materials->fromAudio(
            $this->dictationAudio,
            MaterialContext::Dictation,
            MaterialSource::Recording,
            $this->dictationUser(),
            $this->dictationOrganization(),
        );

        if ($material->audio_seconds === null) {
            $material->update(['audio_seconds' => max(0, min($seconds, (int) config('megis.materials.dictation_max_seconds')))]);
        }

        $this->dictationAudio = null;
        $this->dictations[$field] = $material->id;
        $pipeline->start($material);
    }

    public function pollDictations(): void
    {
        foreach ($this->dictations as $field => $materialId) {
            $material = Material::query()->whereKey($materialId)->where('user_id', $this->dictationUser()?->id)->first();

            if ($material === null) {
                unset($this->dictations[$field]);

                continue;
            }

            if ($material->status === MaterialStatus::Processed) {
                unset($this->dictations[$field]);
                $this->applyDictation($field, trim((string) $material->text));
            } elseif ($material->status === MaterialStatus::Failed) {
                unset($this->dictations[$field]);
                $this->addError($field, __('diagnosis.dictation.failed'));
            }
        }
    }

    /**
     * The transcript is added after what is already in the field.
     */
    protected function applyDictation(string $field, string $text): void
    {
        $current = trim((string) data_get($this, $field));

        data_set($this, $field, trim($current.' '.$text));
        $this->dictated($field);
    }

    /**
     * Called after a transcript went into a field, e.g. to save it.
     */
    protected function dictated(string $field): void {}

    /**
     * Whose recording it is: the customer; the admin's tab says the admin.
     */
    protected function dictationUser(): ?User
    {
        return auth('web')->user();
    }

    abstract protected function canDictate(string $field): bool;

    abstract protected function dictationOrganization(): ?Organization;
}
