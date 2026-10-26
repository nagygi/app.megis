<?php

namespace App\Livewire\Admin\Capabilities;

use App\Models\Capability;
use App\Models\CapabilityImport;
use App\Services\Capabilities\CapabilityImporter;
use App\Services\Capabilities\CapabilityImportResult;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use OpenSpout\Common\Exception\OpenSpoutException;

/**
 * Képességtár → Képesség Import (07 · 7.6, NM-19): upload the xlsx
 * export, see how many capabilities and patterns are new, changed and
 * unchanged and which fields changed, then "Betöltöm". The file is not kept.
 */
class Import extends Component
{
    use WithFileUploads;

    public ?TemporaryUploadedFile $file = null;

    /** @var array<string, mixed>|null the result of the preview */
    public ?array $preview = null;

    public function mount(): void
    {
        Gate::authorize('import', Capability::class);
    }

    public function updatedFile(): void
    {
        $this->preview = null;
    }

    public function showPreview(CapabilityImporter $importer): void
    {
        Gate::authorize('import', Capability::class);

        $this->preview = $this->run(fn (string $path) => $importer->preview($path))->toArray();
    }

    public function load(CapabilityImporter $importer): void
    {
        Gate::authorize('import', Capability::class);
        abort_if($this->preview === null, 409);

        $result = $this->run(fn (string $path) => $importer->import($path, $this->file->getClientOriginalName()));

        $this->reset(['file', 'preview']);
        session()->flash('status', __('admin.capabilities.import.loaded', [
            'created' => count($result->created),
            'updated' => count($result->updated),
            'unchanged' => count($result->unchanged),
            'patterns_created' => count($result->patternsCreated),
            'patterns_updated' => count($result->patternsUpdated),
            'rules_changed' => count($result->rulesChanged),
        ]));
    }

    public function cancel(): void
    {
        $this->reset(['file', 'preview']);
    }

    public function render(): View
    {
        return view('livewire.admin.capabilities.import', [
            'imports' => CapabilityImport::query()->with('creator')->latest('id')->limit(10)->get(),
        ])->layout('layouts::admin')->title(__('admin.capabilities.import.title'));
    }

    /**
     * Runs the importer on a local copy, as the upload may sit on S3.
     *
     * @param  callable(string): CapabilityImportResult  $callback
     */
    private function run(callable $callback): CapabilityImportResult
    {
        $this->validate([
            'file' => ['required', 'file', 'extensions:xlsx', 'max:10240'],
        ], attributes: ['file' => __('admin.capabilities.import.file')]);

        $temporary = tempnam(sys_get_temp_dir(), 'kepessegtar');
        $path = $temporary.'.xlsx';
        rename($temporary, $path);

        try {
            file_put_contents($path, $this->file->get());

            return $callback($path);
        } catch (OpenSpoutException) {
            throw ValidationException::withMessages(['file' => __('admin.capabilities.import.invalid_file')]);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
