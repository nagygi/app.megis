<?php

namespace App\Livewire\Admin\Values;

use App\Models\Value;
use App\Models\ValueImport;
use App\Services\Values\ValueImporter;
use App\Services\Values\ValueImportResult;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use OpenSpout\Common\Exception\OpenSpoutException;

/**
 * Értéktár → Érték Import (03 · 3.6): upload the mégis table, see how many
 * values are new, changed and unchanged and which fields changed, then
 * "Betöltöm". Only a superadmin loads; an admin sees the page inactive. The
 * file is not kept.
 */
class Import extends Component
{
    use WithFileUploads;

    public ?TemporaryUploadedFile $file = null;

    /** @var array<string, mixed>|null the result of the preview */
    public ?array $preview = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', Value::class);
    }

    public function updatedFile(): void
    {
        $this->preview = null;
    }

    public function showPreview(ValueImporter $importer): void
    {
        Gate::authorize('import', Value::class);

        $this->preview = $this->run(fn (string $path) => $importer->import($path, dryRun: true))->toArray();
    }

    public function load(ValueImporter $importer): void
    {
        Gate::authorize('import', Value::class);
        abort_if($this->preview === null, 409);

        $result = $this->run(fn (string $path) => $importer->import($path, false, $this->file->getClientOriginalName()));

        // The library changed since the preview: nothing came in, the reasons are shown.
        if ($result->failed()) {
            $this->preview = $result->toArray();

            return;
        }

        $this->reset(['file', 'preview']);
        session()->flash('status', __('admin.values.import.loaded', [
            'created' => count($result->created),
            'updated' => count($result->updated),
            'unchanged' => count($result->unchanged),
            'categories' => count($result->createdCategories),
        ]));
    }

    public function cancel(): void
    {
        $this->reset(['file', 'preview']);
    }

    public function render(): View
    {
        return view('livewire.admin.values.import', [
            'canImport' => auth()->user()->can('import', Value::class),
            'imports' => ValueImport::query()->with('creator')->latest('id')->limit(10)->get(),
        ])->layout('layouts::admin')->title(__('admin.values.import.title'));
    }

    /**
     * Runs the importer on a local copy, as the upload may sit on S3. The copy
     * keeps the extension: the reader picks xlsx or csv by it.
     *
     * @param  callable(string): ValueImportResult  $callback
     */
    private function run(callable $callback): ValueImportResult
    {
        $this->validate([
            'file' => ['required', 'file', 'extensions:xlsx,csv', 'max:10240'],
        ], attributes: ['file' => __('admin.values.import.file')]);

        $temporary = tempnam(sys_get_temp_dir(), 'ertektar');
        $path = $temporary.'.'.strtolower($this->file->getClientOriginalExtension());
        rename($temporary, $path);

        try {
            file_put_contents($path, $this->file->get());

            return $callback($path);
        } catch (OpenSpoutException) {
            throw ValidationException::withMessages(['file' => __('admin.values.import.invalid_file')]);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
