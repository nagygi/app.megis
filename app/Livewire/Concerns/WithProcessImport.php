<?php

namespace App\Livewire\Concerns;

use App\Enums\ProcessRhythm;
use App\Models\Organization;
use App\Models\Process;
use App\Services\Processes\ProcessImporter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Several processes at once from a written or spoken text (11 · 11.2, 11.6):
 * the AI reads the rows, the user edits or removes them, then adds them. The
 * component says who may do it and for which organization; with WithDictation
 * the text can be spoken.
 */
trait WithProcessImport
{
    public string $importText = '';

    /**
     * The preview rows, editable until they are added.
     *
     * @var list<array{key: string, name: string, rhythm: ?string, description: ?string, parent: ?string}>
     */
    public array $importRows = [];

    public function parseImport(ProcessImporter $importer): void
    {
        $this->authorizeImport();
        $this->validate(['importText' => ['required', 'string', 'min:3', 'max:10000']], attributes: ['importText' => __('processes.import.text')]);

        try {
            $rows = $importer->parse($this->importOrganization(), $this->importText);
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('importText', __('processes.import.failed'));

            return;
        }

        if ($rows === []) {
            $this->addError('importText', __('processes.import.none'));

            return;
        }

        $this->importRows = $rows;
    }

    /**
     * The row's subprocesses stay, on the top level.
     */
    public function removeImportRow(string $key): void
    {
        $this->importRows = collect($this->importRows)
            ->reject(fn (array $row) => $row['key'] === $key)
            ->map(fn (array $row) => $row['parent'] === 'new:'.$key ? [...$row, 'parent' => null] : $row)
            ->values()
            ->all();
    }

    public function backToImportText(): void
    {
        $this->reset('importRows');
        $this->resetValidation();
    }

    public function cancelImport(): void
    {
        $this->reset(['importText', 'importRows']);
        $this->resetValidation();
    }

    public function importProcesses(ProcessImporter $importer): void
    {
        $this->authorizeImport();
        $organization = $this->importOrganization();

        $this->validate([
            'importRows' => ['required', 'array'],
            'importRows.*.name' => ['required', 'string', 'min:2', 'max:255'],
            'importRows.*.rhythm' => ['nullable', Rule::enum(ProcessRhythm::class)],
            'importRows.*.description' => ['nullable', 'string', 'max:2000'],
            'importRows.*.parent' => ['nullable', Rule::in([
                ...collect($this->importRows)->map(fn (array $row) => 'new:'.$row['key']),
                ...Process::query()->whereBelongsTo($organization)->whereNull('parent_id')->pluck('id')->map(fn ($id) => 'process:'.$id),
            ])],
        ], attributes: [
            'importRows.*.name' => __('processes.list.fields.name'),
            'importRows.*.rhythm' => __('processes.list.fields.rhythm'),
            'importRows.*.description' => __('processes.list.fields.description'),
            'importRows.*.parent' => __('processes.list.fields.parent'),
        ]);

        // Two levels at most (11 · 11.2): a row with subprocesses has no parent.
        $parents = collect($this->importRows)->pluck('parent')->filter()->all();
        $errors = collect($this->importRows)
            ->filter(fn (array $row) => filled($row['parent']) && in_array('new:'.$row['key'], $parents, true))
            ->mapWithKeys(fn (array $row, int $index) => ["importRows.{$index}.parent" => __('processes.two_levels')]);

        if ($errors->isNotEmpty()) {
            throw ValidationException::withMessages($errors->all());
        }

        $count = $importer->import($organization, $this->importRows);

        $this->reset(['importText', 'importRows']);
        session()->flash('status', trans_choice('processes.import.done', $count, ['count' => $count]));
    }

    abstract protected function authorizeImport(): void;

    abstract protected function importOrganization(): Organization;
}
