<?php

namespace App\Services\Materials;

use App\Enums\MaterialContext;
use App\Enums\MaterialSource;
use App\Enums\MaterialStatus;
use App\Exceptions\UnreadableMaterialException;
use App\Models\Material;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Takes in materials and keeps their files on the private disk (06 · 6.6).
 * Text files are read on arrival; audio waits for transcription.
 */
class MaterialStore
{
    public function __construct(
        private TextExtractor $extractor,
        private AudioInspector $inspector,
        private TranscriptParser $transcripts,
    ) {}

    public function fromText(string $text, MaterialContext $context, ?User $owner = null, ?Organization $organization = null): Material
    {
        return Material::create([
            'context' => $context,
            'source' => MaterialSource::Text,
            'user_id' => $owner?->id,
            'organization_id' => $organization?->id,
            'text' => trim($text),
            'segments' => [['speaker' => TranscriptParser::SINGLE_SPEAKER, 'start' => null, 'end' => null, 'text' => trim($text)]],
            'status' => MaterialStatus::Pending,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * An audio file: kept as uploaded, measured with ffprobe when available.
     */
    public function fromAudio(UploadedFile $file, MaterialContext $context, MaterialSource $source, ?User $owner = null, ?Organization $organization = null): Material
    {
        $material = new Material([
            'context' => $context,
            'source' => $source,
            'user_id' => $owner?->id,
            'organization_id' => $organization?->id,
            'original_name' => $source === MaterialSource::AudioUpload ? $file->getClientOriginalName() : null,
            'audio_mime' => $file->getMimeType(),
            'status' => MaterialStatus::Pending,
            'created_by' => auth()->id(),
        ]);
        $material->uuid = (string) Str::uuid();

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'webm');
        $material->audio_path = $file->storeAs($material->uuid, 'audio.'.$extension, Material::DISK);
        $material->audio_seconds = $this->inspector->durationSeconds($file->getRealPath() ?: '');
        $material->save();

        return $material;
    }

    /**
     * A transcript or document: read now, with its speaker labels kept (06 · 6.3).
     */
    public function fromTextFile(UploadedFile $file, MaterialContext $context, ?User $owner = null, ?Organization $organization = null): Material
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $text = $this->extractor->extract($file->getRealPath() ?: '', $extension);

        return Material::create([
            'context' => $context,
            'source' => MaterialSource::TextUpload,
            'user_id' => $owner?->id,
            'organization_id' => $organization?->id,
            'original_name' => $file->getClientOriginalName(),
            'text' => $text,
            'segments' => array_map(
                fn (array $segment): array => [...$segment, 'start' => null, 'end' => null],
                $this->transcripts->parse($text),
            ),
            'status' => MaterialStatus::Pending,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Only the audio goes; the transcript and the diagnosis stay (06 · 6.6).
     */
    public function deleteAudio(Material $material): void
    {
        if ($material->audio_path !== null) {
            Storage::disk(Material::DISK)->delete($material->audio_path);
        }

        $material->forceFill(['audio_path' => null, 'audio_deleted_at' => now()])->save();
    }

    /**
     * Everything of the material, files included (anonymous mirror expiry, account deletion).
     */
    public function delete(Material $material): void
    {
        Storage::disk(Material::DISK)->deleteDirectory($material->uuid);
        $material->delete();
    }

    /**
     * The audio for an authorized listener: a short-lived signed link on S3,
     * a stream from the local disk.
     */
    public function audioResponse(Material $material): Response
    {
        abort_unless($material->hasAudio(), 404);

        $disk = Storage::disk(Material::DISK);

        if (config('filesystems.disks.'.Material::DISK.'.driver') === 's3') {
            return redirect()->away($disk->temporaryUrl($material->audio_path, now()->addMinutes(10)));
        }

        /** @var StreamedResponse $response */
        $response = $disk->response($material->audio_path, null, [
            'Content-Type' => $material->audio_mime ?? 'audio/webm',
            'Cache-Control' => 'private, no-store',
        ]);

        return $response;
    }

    /**
     * Upload rules shown on every form (NFR · 3).
     *
     * @return array{audio: string, text: string, audio_max_mb: int, audio_max_minutes: int, text_max_mb: int}
     */
    public static function rules(): array
    {
        return [
            'audio' => implode(', ', config('megis.materials.audio_extensions')),
            'text' => implode(', ', config('megis.materials.text_extensions')),
            'audio_max_mb' => (int) (config('megis.materials.audio_max_kilobytes') / 1024),
            'audio_max_minutes' => (int) config('megis.materials.audio_max_minutes'),
            'text_max_mb' => (int) (config('megis.materials.text_max_kilobytes') / 1024),
        ];
    }

    /**
     * Refuses audio longer than allowed when its length is known (06 · 6.3).
     */
    public function ensureAudioLength(Material $material): void
    {
        $maxSeconds = (int) config('megis.materials.audio_max_minutes') * 60;

        if ($material->audio_seconds !== null && $material->audio_seconds > $maxSeconds) {
            $this->delete($material);

            throw new UnreadableMaterialException(__('materials.too_long', ['minutes' => config('megis.materials.audio_max_minutes')]));
        }
    }
}
