<?php

namespace App\Livewire\Admin\ValueCategories;

use App\Models\ValueCategory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * New and edited value category: name (unique), English name, higher order
 * value and description (03 · 3.4).
 */
class Form extends Component
{
    public ?ValueCategory $category = null;

    public string $name = '';

    public string $name_en = '';

    public string $higher_order = '';

    public string $description = '';

    public function mount(?ValueCategory $category = null): void
    {
        if ($category?->exists) {
            Gate::authorize('update', $category);
            $this->category = $category;
            $this->name = $category->name;
            $this->name_en = (string) $category->name_en;
            $this->higher_order = (string) $category->higher_order;
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
            'name_en' => ['nullable', 'string', 'max:255'],
            'higher_order' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ], attributes: [
            'name_en' => __('admin.value_categories.name_en'),
            'higher_order' => __('admin.value_categories.higher_order'),
        ]);

        $attributes = [
            'name' => trim($validated['name']),
            'name_en' => filled(trim($validated['name_en'])) ? trim($validated['name_en']) : null,
            'higher_order' => filled(trim($validated['higher_order'])) ? trim($validated['higher_order']) : null,
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
