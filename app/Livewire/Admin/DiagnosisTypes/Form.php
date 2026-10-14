<?php

namespace App\Livewire\Admin\DiagnosisTypes;

use App\Models\DiagnosisType;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * New and edited diagnosis type: name (unique) and description (04 · 4.1).
 */
class Form extends Component
{
    public ?DiagnosisType $type = null;

    public string $name = '';

    public string $description = '';

    public function mount(?DiagnosisType $type = null): void
    {
        if ($type?->exists) {
            Gate::authorize('update', $type);
            $this->type = $type;
            $this->name = $type->name;
            $this->description = (string) $type->description;

            return;
        }

        Gate::authorize('create', DiagnosisType::class);
    }

    public function save(): void
    {
        $this->type === null
            ? Gate::authorize('create', DiagnosisType::class)
            : Gate::authorize('update', $this->type);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('diagnosis_types', 'name')->ignore($this->type?->id)],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $attributes = [
            'name' => trim($validated['name']),
            'description' => filled($validated['description']) ? $validated['description'] : null,
        ];

        $this->type === null ? DiagnosisType::create($attributes) : $this->type->update($attributes);

        session()->flash('status', __('admin.common.saved'));
        $this->redirectRoute('admin.diagnosis-types.index', navigate: false);
    }

    public function render(): View
    {
        $title = $this->type ? __('admin.diagnosis_types.edit_title') : __('admin.diagnosis_types.create_title');

        return view('livewire.admin.diagnosis-types.form', ['title' => $title])
            ->layout('layouts::admin')
            ->title($title);
    }
}
