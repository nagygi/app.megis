<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\LegalDocumentType;
use App\Models\LegalDocumentVersion;
use App\Services\Legal\LegalDocuments as LegalDocumentService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Beállítások → Jogi dokumentumok (05 · 5.1). Versions are never deleted.
 */
class LegalDocuments extends Component
{
    use WithFileUploads;

    /**
     * The document whose upload form is open.
     */
    public ?string $uploadingType = null;

    public ?TemporaryUploadedFile $file = null;

    public string $effectiveFrom = '';

    public bool $requiresReacceptance = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', LegalDocumentVersion::class);
    }

    public function openUpload(string $type): void
    {
        $this->uploadingType = LegalDocumentType::from($type)->value;
        $this->reset(['file', 'requiresReacceptance']);
        $this->effectiveFrom = today()->toDateString();
        $this->resetValidation();
    }

    public function closeUpload(): void
    {
        $this->reset(['uploadingType', 'file', 'effectiveFrom', 'requiresReacceptance']);
    }

    public function storeVersion(LegalDocumentService $legalDocuments): void
    {
        Gate::authorize('create', LegalDocumentVersion::class);

        $validated = $this->validate([
            'uploadingType' => ['required', Rule::enum(LegalDocumentType::class)],
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'effectiveFrom' => ['required', 'date'],
            'requiresReacceptance' => ['boolean'],
        ]);

        $legalDocuments->store(
            LegalDocumentType::from($validated['uploadingType']),
            $this->file,
            CarbonImmutable::parse($validated['effectiveFrom']),
            $validated['requiresReacceptance'],
        );

        $this->closeUpload();
        session()->flash('status', __('admin.legal.uploaded_ok'));
    }

    public function render(): View
    {
        $legalDocuments = app(LegalDocumentService::class);

        $versionsByType = LegalDocumentVersion::query()
            ->with('creator')
            ->newestFirst()
            ->get()
            ->groupBy(fn (LegalDocumentVersion $version) => $version->type->value);

        $documents = collect(LegalDocumentType::cases())->map(fn (LegalDocumentType $type) => [
            'type' => $type,
            'current' => $legalDocuments->current($type),
            'versions' => $versionsByType->get($type->value, collect()),
        ]);

        return view('livewire.admin.settings.legal-documents', ['documents' => $documents])
            ->layout('layouts::admin')
            ->title(__('admin.legal.title'));
    }
}
