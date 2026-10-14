<?php

namespace App\Livewire\Admin\ValueCategories;

use App\Models\ValueCategory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * New and edited value category: name (unique) and description (03 · 3.4).
 */
class Form extends Component
{
    public ?ValueCategory $category = null;

    public string $name = '';

    public string $description = '';

    public function mount(?ValueCategory $category = null): void
    {
        if ($category?->exists) {
            Gate::authorize('update', $category);
            $this->category = $category;
            $this->name = $category->name;
            $this->description = (string) $category->description;

            return;
        }

        Gate::authorize('create', ValueCategory::class);
    }

    public function save(): void
    {
        $this->category === null
            ? Gate::authorize('create', ValueCategory::class)
            : Gate::authorize('update', $this->category);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('value_categories', 'name')->ignore($this->category?->id)],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $attributes = [
            'name' => trim($validated['name']),
            'description' => filled($validated['description']) ? $validated['description'] : null,
        ];

        $this->category === null ? ValueCategory::create($attributes) : $this->category->update($attributes);

        session()->flash('status', __('admin.common.saved'));
        $this->redirectRoute('admin.value-categories.index', navigate: false);
    }

    public function render(): View
    {
        $title = $this->category ? __('admin.value_categories.edit_title') : __('admin.value_categories.create_title');

        return view('livewire.admin.value-categories.form', ['title' => $title])
            ->layout('layouts::admin')
            ->title($title);
    }
}
