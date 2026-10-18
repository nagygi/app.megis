<?php

namespace App\Livewire\Admin\Values;

use App\Enums\ValueGrade;
use App\Enums\ValueSvsStatus;
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

    /**
     * Set once, then fixed: the table load matches on it (03 · 3.6).
     */
    public string $code = '';

    public string $name = '';

    public string $name_en = '';

    public string $slug = '';

    /**
     * The slug follows the name until someone unlocks it with "Szerkesztés".
     */
    public bool $slugLocked = true;

    public ?int $value_category_id = null;

    public string $definition = '';

    public string $definition_en = '';

    /** @var list<string> */
    public array $meanings = [];

    /** @var list<string> */
    public array $non_meanings = [];

    /** @var list<string> */
    public array $false_positives = [];

    /** @var list<string> */
    public array $overlaps = [];

    /** @var list<string> */
    public array $high_examples = [];

    /** @var list<string> */
    public array $low_examples = [];

    /** @var list<string> */
    public array $examples = [];

    /** @var list<string> */
    public array $keywords = [];

    /** @var list<string> */
    public array $literature = [];

    public string $evidence = '';

    public string $transposability = '';

    public string $svs_status = '';

    public function mount(?Value $value = null): void
    {
        if ($value?->exists) {
            Gate::authorize('update', $value);

            $this->value = $value;
            $this->code = (string) $value->code;
            $this->name = $value->name;
            $this->name_en = (string) $value->name_en;
            $this->slug = $value->slug;
            $this->value_category_id = $value->value_category_id;
            $this->definition = (string) $value->definition;
            $this->definition_en = (string) $value->definition_en;
            $this->evidence = (string) $value->evidence?->value;
            $this->transposability = (string) $value->transposability?->value;
            $this->svs_status = (string) $value->svs_status?->value;

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

        foreach (['code', 'name', 'name_en', 'definition', 'definition_en'] as $field) {
            $this->{$field} = trim($this->{$field});
        }

        $this->cleanLists();
        $codeEditable = $this->value?->code === null;

        $validated = $this->validate([
            'code' => $codeEditable
                ? ['nullable', 'string', 'max:20', Rule::unique('values', 'code')->ignore($this->value?->id)]
                : [],
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
            'name_en' => ['nullable', 'string', 'max:255'],
            'value_category_id' => ['nullable', Rule::exists('value_categories', 'id')],
            'definition' => ['nullable', 'string', 'max:1000'],
            'definition_en' => ['nullable', 'string', 'max:1000'],
            ...$this->listRules('meanings'),
            ...$this->listRules('non_meanings'),
            ...$this->listRules('false_positives'),
            ...$this->listRules('overlaps'),
            ...$this->listRules('high_examples', required: true),
            ...$this->listRules('low_examples', required: true),
            ...$this->listRules('examples'),
            ...$this->listRules('keywords'),
            ...$this->listRules('literature', maxLength: Value::LITERATURE_ITEM_MAX_LENGTH),
            'evidence' => ['nullable', Rule::enum(ValueGrade::class)],
            'transposability' => ['nullable', Rule::enum(ValueGrade::class)],
            'svs_status' => ['nullable', Rule::enum(ValueSvsStatus::class)],
        ], attributes: $this->validationAttributes());

        $attributes = [
            'name' => $validated['name'],
            'name_en' => filled($validated['name_en']) ? $validated['name_en'] : null,
            'slug' => $validated['slug'],
            'value_category_id' => $validated['value_category_id'],
            'definition' => filled($validated['definition']) ? $validated['definition'] : null,
            'definition_en' => filled($validated['definition_en']) ? $validated['definition_en'] : null,
            ...array_intersect_key($validated, array_flip(Value::LISTS)),
            'evidence' => ValueGrade::tryFrom($validated['evidence']),
            'transposability' => ValueGrade::tryFrom($validated['transposability']),
            'svs_status' => ValueSvsStatus::tryFrom($validated['svs_status']),
        ];

        if ($codeEditable && filled($validated['code'])) {
            $attributes['code'] = $validated['code'];
        }

        if ($this->value === null) {
            $value = $values->create($attributes);

            session()->flash('status', __('admin.values.created'));
            $this->redirectRoute('admin.values.edit', $value, navigate: false);

            return;
        }

        $version = $values->update($this->value, $attributes);
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
            'categories' => ValueCategory::query()->orderBy('name')->get(['id', 'name', 'name_en']),
            'grades' => collect(ValueGrade::cases())->mapWithKeys(fn (ValueGrade $grade) => [$grade->value => $grade->label()])->all(),
            'svsStatuses' => collect(ValueSvsStatus::cases())->mapWithKeys(fn (ValueSvsStatus $status) => [$status->value => $status->label()])->all(),
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

        foreach (['code', 'name_en', 'definition', 'definition_en', 'evidence', 'transposability', 'svs_status'] as $field) {
            $attributes[$field] = __('admin.values.fields.'.$field);
        }

        foreach (Value::LISTS as $list) {
            $attributes[$list] = __('admin.values.fields.'.$list);
            $attributes[$list.'.*'] = __('admin.values.fields.'.$list);
        }

        return $attributes;
    }
}
