<?php

namespace App\Services\Legal;

use App\Enums\LegalDocumentType;
use App\Models\LegalAcceptance;
use App\Models\LegalDocumentVersion;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The legal documents in force and their acceptance (05, 14 · 14.5).
 */
class LegalDocuments
{
    public const DISK = 'materials';

    /**
     * @var array<string, ?LegalDocumentVersion>
     */
    private array $currentVersions = [];

    public function current(LegalDocumentType $type): ?LegalDocumentVersion
    {
        return $this->currentVersions[$type->value] ??= LegalDocumentVersion::currentFor($type);
    }

    /**
     * The versions in force of the documents that have to be accepted (ÁSZF, privacy policy).
     *
     * @return Collection<int, LegalDocumentVersion>
     */
    public function currentAcceptable(): Collection
    {
        return collect(LegalDocumentType::cases())
            ->filter(fn (LegalDocumentType $type): bool => $type->requiresAcceptance())
            ->map(fn (LegalDocumentType $type): ?LegalDocumentVersion => $this->current($type))
            ->filter()
            ->values();
    }

    /**
     * One row per document, so both versions are visible separately (14 · 14.5).
     *
     * @param  Collection<int, LegalDocumentVersion>  $versions
     */
    public function recordAcceptance(User $user, Collection $versions): void
    {
        foreach ($versions as $version) {
            LegalAcceptance::firstOrCreate(
                ['user_id' => $user->id, 'legal_document_version_id' => $version->id],
                ['accepted_at' => now()],
            );
        }
    }

    /**
     * Versions in force that require re-acceptance and that the user has not accepted yet.
     *
     * @return Collection<int, LegalDocumentVersion>
     */
    public function pendingReacceptance(User $user): Collection
    {
        $acceptedVersionIds = LegalAcceptance::query()
            ->where('user_id', $user->id)
            ->pluck('legal_document_version_id');

        return $this->currentAcceptable()
            ->filter(fn (LegalDocumentVersion $version): bool => $version->requires_reacceptance
                && ! $acceptedVersionIds->contains($version->id))
            ->values();
    }

    public function store(
        LegalDocumentType $type,
        UploadedFile $file,
        CarbonInterface $effectiveFrom,
        bool $requiresReacceptance,
    ): LegalDocumentVersion {
        $path = $file->storeAs(
            'legal/'.$type->value,
            Str::uuid().'.pdf',
            ['disk' => self::DISK],
        );

        return LegalDocumentVersion::create([
            'type' => $type,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'effective_from' => $effectiveFrom->toDateString(),
            'requires_reacceptance' => $type->requiresAcceptance() && $requiresReacceptance,
        ]);
    }
}
