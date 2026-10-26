<?php

namespace App\Livewire\Concerns;

use App\Ai\AiContext;
use App\Enums\MaterialContext;
use App\Enums\MaterialSource;
use App\Enums\MaterialStatus;
use App\Exceptions\UnreadableMaterialException;
use App\Models\Material;
use App\Models\Organization;
use App\Models\User;
use App\Services\Materials\MaterialPipeline;
use App\Services\Materials\MaterialStore;
use App\Services\Materials\TextCondenser;
use App\Services\Materials\TextExtractor;
use Illuminate\Support\Facades\Validator;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

/**
 * A text field can be spoken or filled from a file instead of typed (08 · 8.1,
 * 10 · 10.1, 24; Leírom · Elmondom · Feltöltöm): the transcript or the file's
 * text goes into the field, editable. The recording itself is kept like every
 * recording (06 · 6.6); the file is only read. What does not fit the field
 * (dictationLimit) the AI condenses. The component uses WithFileUploads and
 * says which fields can be dictated.
 */
trait WithDictation
{
    /** @var TemporaryUploadedFile|null */
    public $dictationAudio = null;

    /** @var TemporaryUploadedFile|null */
    public $dictationFile = null;

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
     * Feltöltöm: the text of a document (txt, md, docx, pdf) into the field.
     * A wrong or unreadable file is the field's error.
     */
    public function readFile(string $field, TextExtractor $extractor): void
    {
        abort_unless($this->canDictate($field), 403);
        $this->resetErrorBag($field);

        $file = $this->dictationFile;
        $this->dictationFile = null;

        $validator = Validator::make(['file' => $file], ['file' => [
            'required',
            'file',
            'max:'.config('megis.materials.text_max_kilobytes'),
            'extensions:'.implode(',', config('megis.materials.text_extensions')),
        ]], [], ['file' => __('ui.long_text.file')]);

        if ($validator->fails()) {
            $this->addError($field, $validator->errors()->first('file'));

            return;
        }

        try {
            $text = $extractor->extract($file->getRealPath() ?: '', $file->getClientOriginalExtension());
        } catch (UnreadableMaterialException $exception) {
            $this->addError($field, $exception->getMessage());

            return;
        } finally {
            $file->delete();
        }

        $this->applyDictation($field, $text, fromFile: true);
    }

    /**
     * The text is added after what is already in the field; the browser hears
     * of it (dictation-applied) with a word if it had to be condensed.
     */
    protected function applyDictation(string $field, string $text, bool $fromFile = false): void
    {
        $current = trim((string) data_get($this, $field));
        $combined = trim($current.' '.$text);
        $limit = $this->dictationLimit($field);
        $notice = null;

        if ($limit !== null && mb_strlen($combined) > $limit) {
            [$combined, $notice] = $this->fitDictation($current, trim($text), $limit, $fromFile);
        }

        data_set($this, $field, $combined);
        $this->dictated($field);
        $this->dispatch('dictation-applied', field: $field, notice: $notice);
    }

    /**
     * What does not fit is condensed: the new text into the room left, or,
     * with little room left, together with what was there. Without the AI
     * the end is cut.
     *
     * @return array{0: string, 1: string}
     */
    private function fitDictation(string $current, string $text, int $limit, bool $fromFile): array
    {
        $room = $current === '' ? $limit : $limit - mb_strlen($current) - 1;
        $together = $room < min(300, intdiv($limit, 2));
        $organization = $this->dictationOrganization();
        $context = $organization === null ? new AiContext : AiContext::forOrganization($organization);

        try {
            $condensed = app(TextCondenser::class)->condense($together ? trim($current.' '.$text) : $text, $together ? $limit : $room, $context);
        } catch (Throwable $exception) {
            report($exception);

            return [mb_substr(trim($current.' '.$text), 0, $limit), __('ui.long_text.cut', ['limit' => $this->dictationNumber($limit)])];
        }

        if ($together) {
            return [$condensed, __('ui.long_text.condensed_all', ['to' => $this->dictationNumber(mb_strlen($condensed))])];
        }

        return [trim($current.' '.$condensed), __('ui.long_text.condensed.'.($fromFile ? 'file' : 'speech'), [
            'from' => $this->dictationNumber(mb_strlen($text)),
            'to' => $this->dictationNumber(mb_strlen($condensed)),
        ])];
    }

    private function dictationNumber(int $number): string
    {
        return number_format($number, 0, ',', "\u{A0}");
    }

    /**
     * The field's length limit; a longer text is condensed to it. Null: no limit.
     */
    protected function dictationLimit(string $field): ?int
    {
        return null;
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
