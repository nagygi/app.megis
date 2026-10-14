<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Area;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Beállítások → Területek (18 · 18.1): the five areas of a Teljes diagnosis
 * with "Mit nézünk", its trace and the description of a 6. The report
 * (R4·b) and the Bölcsességtár (BT3) read them; the list itself is fixed.
 */
class Areas extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $whatWeLookAt = '';

    public string $trace = '';

    public string $sixDescription = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Area::class);
    }

    public function edit(int $areaId): void
    {
        $area = Area::query()->findOrFail($areaId);
        Gate::authorize('update', $area);

        $this->editingId = $area->id;
        $this->name = $area->name;
        $this->whatWeLookAt = (string) $area->what_we_look_at;
        $this->trace = (string) $area->trace;
        $this->sixDescription = (string) $area->six_description;
        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->reset(['editingId', 'name', 'whatWeLookAt', 'trace', 'sixDescription']);
        $this->resetValidation();
    }

    public function save(): void
    {
        $area = Area::query()->findOrFail((int) $this->editingId);
        Gate::authorize('update', $area);

        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'whatWeLookAt' => ['nullable', 'string', 'max:1000'],
            'trace' => ['nullable', 'string', 'max:1000'],
            'sixDescription' => ['nullable', 'string', 'max:1000'],
        ], attributes: [
            'name' => __('admin.areas.name'),
            'whatWeLookAt' => __('admin.areas.what_we_look_at'),
            'trace' => __('admin.areas.trace'),
            'sixDescription' => __('admin.areas.six_description'),
        ]);

        $area->update([
            'name' => trim($validated['name']),
            'what_we_look_at' => filled($validated['whatWeLookAt']) ? trim($validated['whatWeLookAt']) : null,
            'trace' => filled($validated['trace']) ? trim($validated['trace']) : null,
            'six_description' => filled($validated['sixDescription']) ? trim($validated['sixDescription']) : null,
        ]);

        $this->cancel();
        session()->flash('status', __('admin.common.saved'));
    }

    public function render(): View
    {
        return view('livewire.admin.settings.areas', [
            'areas' => Area::query()->orderBy('position')->get(),
        ])->layout('layouts::admin')->title(__('admin.areas.title'));
    }
}
