<?php

namespace App\Livewire\Client;

use App\Models\Organization;
use App\Models\Value;
use App\Models\ValueCategory;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Tárak → Értéktár (30): the organization's values in its own words, and the
 * mégis Értéktár to browse. The own values (24) and the scores (27) come
 * with their tickets.
 */
class ValueLibrary extends Component
{
    public Organization $organization;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'kategoria', except: '')]
    public string $category = '';

    public ?int $openValueId = null;

    public ?string $openItemKey = null;

    public function mount(Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function toggleValue(int $valueId): void
    {
        $this->openValueId = $this->openValueId === $valueId ? null : $valueId;
    }

    public function toggleItem(string $key): void
    {
        $this->openItemKey = $this->openItemKey === $key ? null : $key;
    }

    public function render(): View
    {
        $valueSet = $this->organization->valueSet()->with(['items', 'latestVersion'])->first();

        // 30.4: active, reviewed values only, read-only.
        $library = Value::query()
            ->availableTo(null)
            ->with('category')
            ->when(trim($this->search) !== '', fn ($query) => $query->whereLike('search_text', '%'.mb_strtolower(trim($this->search)).'%'))
            ->when($this->category !== '', fn ($query) => $query->where('value_category_id', $this->category))
            ->orderBy('name')
            ->get();

        return view('livewire.client.value-library', [
            'valueSet' => $valueSet !== null && $valueSet->items->isNotEmpty() ? $valueSet : null,
            'library' => $library,
            'libraryCount' => Value::query()->availableTo(null)->count(),
            'categories' => ValueCategory::query()->whereHas('values', fn ($query) => $query->availableTo(null))->orderBy('name')->get(['id', 'name']),
        ])->layout('layouts::client', ['title' => __('client.value_library.title'), 'subtitle' => __('client.value_library.question')]);
    }
}
