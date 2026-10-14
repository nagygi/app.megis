<?php

namespace App\Livewire\Admin\CapabilityGroups;

use App\Models\CapabilityGroup;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * New and edited capability group: Hungarian name (unique), English name,
 * description (07 · 7.4).
 */
class Form extends Component
{
    public ?CapabilityGroup $group = null;

    public string $name = '';

    public string $name_en = '';

    public string $description = '';

    public function mount(?CapabilityGroup $group = null): void
    {
        if ($group?->exists) {
            Gate::authorize('update', $group);
            $this->group = $group;
            $this->name = $group->name;
            $this->name_en = (string) $group->name_en;
            $this->description = (string) $group->description;

            return;
        }

        Gate::authorize('create', CapabilityGroup::class);
    }

    public function save(): void
    {
        $this->group === null
            ? Gate::authorize('create', CapabilityGroup::class)
            : Gate::authorize('update', $this->group);

        $this->name = trim($this->name);
        $this->name_en = trim($this->name_en);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('capability_groups', 'name')->ignore($this->group?->id)],
            'name_en' => ['nullable', 'string', 'max:255', Rule::unique('capability_groups', 'name_en')->ignore($this->group?->id)],
            'description' => ['nullable', 'string', 'max:5000'],
        ], attributes: [
            'name' => __('admin.capability_groups.name'),
            'name_en' => __('admin.capability_groups.name_en'),
        ]);

        $attributes = [
            'name' => $validated['name'],
            'name_en' => filled($validated['name_en']) ? $validated['name_en'] : null,
            'description' => filled($validated['description']) ? $validated['description'] : null,
        ];

        $this->group === null ? CapabilityGroup::create($attributes) : $this->group->update($attributes);

        session()->flash('status', __('admin.common.saved'));
        $this->redirectRoute('admin.capability-groups.index', navigate: false);
    }

    public function render(): View
    {
        $title = $this->group ? __('admin.capability_groups.edit_title') : __('admin.capability_groups.create_title');

        return view('livewire.admin.capability-groups.form', ['title' => $title])
            ->layout('layouts::admin')
            ->title($title);
    }
}
