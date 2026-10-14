<?php

namespace App\Livewire\Admin\Values;

use App\Livewire\Admin\Concerns\EditsLists;
use App\Livewire\Admin\Values\Concerns\ManagesValueState;
use App\Models\RawDetection;
use App\Models\Value;
use App\Models\ValueCategory;
use App\Services\Values\ValueService;
use Closure;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * New and edited Értéktár value (03 · 3.2). A save with changes is a new
 * version; both scale ends are required here, whatever the origin (03 · 3.7).
 */
class Form extends Component
{
    use EditsLists, ManagesValueState;

    public ?Value $value = null;

    public string $name = '';

    public string $slug = '';

    /**
     * The slug follows the name until someone unlocks it with "Szerkesztés".
     */
    public bool $slugLocked = true;

    public ?int $value_category_id = null;

    /** @var list<string> */
    public array $meanings = [];

    /** @var list<string> */
    public array $non_meanings = [];

    /** @var list<string> */
    public array $high_examples = [];

    /** @var list<string> */
    public array $low_examples = [];

    /** @var list<string> */
    public array $examples = [];

    /** @var list<string> */
    public array $keywords = [];

    public function mount(?Value $value = null): void
    {
        if ($value?->exists) {
            Gate::authorize('update', $value);

            $this->value = $value;
            $this->name = $value->name;
            $this->slug = $value->slug;
            $this->value_category_id = $value->value_category_id;

            foreach (Value::LISTS as $list) {
                $this->{$list} = $value->{$list};
            }

            return;
        }

        Gate::authorize('create', Value::class);

        // The two required lists start with an empty row to fill in.
        $this->high_examples = [''];
        $this->low_examples = [''];
    }

    public function updatedName(ValueService $values): void
    {
        if ($this->value === null && $this->slugLocked) {
            $this->slug = $values->slugFor($this->name);
        }
    }

    public function unlockSlug(): void
    {
        $this->slugLocked = false;
    }

    public function save(ValueService $values): void
    {
        $this->value === null
            ? Gate::authorize('create', Value::class)
            : Gate::authorize('update', $this->value);

        $this->name = trim($this->name);
        $this->cleanLists();

        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $name, Closure $fail) use ($values): void {
                    if ($values->findByName((string) $name, $this->value?->id) !== null) {
                        $fail(__('admin.values.name_taken'));
                    }
                },
            ],
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('values', 'slug')->ignore($this->value?->id),
            ],
            'value_category_id' => ['nullable', Rule::exists('value_categories', 'id')],
            ...$this->listRules('meanings'),
            ...$this->listRules('non_meanings'),
            ...$this->listRules('high_examples', required: true),
            ...$this->listRules('low_examples', required: true),
            ...$this->listRules('examples'),
            ...$this->listRules('keywords'),
        ], attributes: $this->validationAttributes());

        if ($this->value === null) {
            $value = $values->create($validated);

            session()->flash('status', __('admin.values.created'));
            $this->redirectRoute('admin.values.edit', $value, navigate: false);

            return;
        }

        $version = $values->update($this->value, $validated);
        $this->slugLocked = true;

        session()->flash('status', $version === null
            ? __('admin.values.saved_unchanged')
            : __('admin.values.saved_version', ['version' => $version->label()]));
    }

    public function render(): View
    {
        $title = $this->value?->name ?? __('admin.values.create_title');

        return view('livewire.admin.values.form', [
            'title' => $title,
            'categories' => ValueCategory::query()->orderBy('name')->get(['id', 'name']),
            // Where an AI-detected value came up, with which quote (03 · 3.7, 06 · 6.4).
            'occurrences' => $this->value === null ? collect() : RawDetection::query()
                ->where('unmatched_value_id', $this->value->id)
                ->with('material.diagnoses')
                ->latest('id')
                ->limit(50)
                ->get(),
        ])->layout('layouts::admin')->title($title);
    }

    protected function editableLists(): array
    {
        return Value::LISTS;
    }

    /**
     * @return array<string, string>
     */
    private function validationAttributes(): array
    {
        $attributes = ['value_category_id' => __('admin.values.fields.category')];

        foreach (Value::LISTS as $list) {
            $attributes[$list] = __('admin.values.fields.'.$list);
            $attributes[$list.'.*'] = __('admin.values.fields.'.$list);
        }

        return $attributes;
    }
}
