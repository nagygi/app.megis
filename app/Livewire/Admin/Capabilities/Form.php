<?php

namespace App\Livewire\Admin\Capabilities;

use App\Enums\CapabilityLevel;
use App\Enums\CapabilityNature;
use App\Enums\CapabilityStatus;
use App\Livewire\Admin\Capabilities\Concerns\ManagesCapabilityState;
use App\Livewire\Admin\Concerns\EditsLists;
use App\Models\Capability;
use App\Models\CapabilityGroup;
use App\Models\Precondition;
use App\Services\Capabilities\CapabilityService;
use Closure;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * New and edited capability (07 · 7.2). A save with changes is a new version,
 * with an optional note (07 · 7.3). The details of the Sheet are read only;
 * the next load from the mégis tár overwrites what is edited here (07, question 1).
 */
class Form extends Component
{
    use EditsLists, ManagesCapabilityState;

    public ?Capability $capability = null;

    public string $code = '';

    public string $name = '';

    public string $name_en = '';

    public string $slug = '';

    /**
     * The slug follows the name until someone unlocks it with "Szerkesztés".
     */
    public bool $slugLocked = true;

    public ?int $capability_group_id = null;

    public string $level = '';

    public string $status = '';

    public string $nature = '';

    /** "", "1" or "0" */
    public string $tempting = '';

    public string $definition = '';

    public string $statement = '';

    /** @var list<string> */
    public array $non_examples = [];

    /** @var list<string> */
    public array $overlaps = [];

    /** @var list<string> */
    public array $keywords = [];

    /** @var array<string, string> level => text */
    public array $appearances = [];

    /** @var array<int, string> step => text */
    public array $stages = [];

    /** @var list<int|string> */
    public array $preconditionIds = [];

    /** @var list<int|string> */
    public array $dependencyIds = [];

    public string $note = '';

    public function mount(?Capability $capability = null): void
    {
        $this->appearances = array_fill_keys(array_map(fn (CapabilityLevel $level) => $level->value, CapabilityLevel::cases()), '');
        $this->stages = array_fill_keys(Capability::STAGES, '');

        if ($capability?->exists) {
            Gate::authorize('update', $capability);

            $this->capability = $capability;
            $this->code = $capability->code;
            $this->name = $capability->name;
            $this->name_en = (string) $capability->name_en;
            $this->slug = $capability->slug;
            $this->capability_group_id = $capability->capability_group_id;
            $this->level = $capability->level->value;
            $this->status = $capability->status->value;
            $this->nature = (string) $capability->nature?->value;
            $this->tempting = $capability->tempting === null ? '' : ($capability->tempting ? '1' : '0');
            $this->definition = $capability->definition;
            $this->statement = (string) $capability->statement;

            foreach (Capability::LISTS as $list) {
                $this->{$list} = $capability->{$list};
            }

            foreach ($this->appearances as $level => $text) {
                $this->appearances[$level] = (string) ($capability->appearances[$level] ?? '');
            }

            foreach ($this->stages as $stage => $text) {
                $this->stages[$stage] = (string) ($capability->stages[$stage] ?? '');
            }

            $this->preconditionIds = $capability->preconditions()->pluck('preconditions.id')->map(fn ($id) => (string) $id)->all();
            $this->dependencyIds = $capability->dependencies()->pluck('capabilities.id')->map(fn ($id) => (string) $id)->all();

            return;
        }

        Gate::authorize('create', Capability::class);
    }

    public function updatedName(CapabilityService $capabilities): void
    {
        if ($this->capability === null && $this->slugLocked) {
            $this->slug = $capabilities->slugFor($this->name);
        }
    }

    public function unlockSlug(): void
    {
        $this->slugLocked = false;
    }

    public function save(CapabilityService $capabilities): void
    {
        $this->capability === null
            ? Gate::authorize('create', Capability::class)
            : Gate::authorize('update', $this->capability);

        foreach (['code', 'name', 'name_en', 'definition', 'statement', 'note'] as $field) {
            $this->{$field} = trim($this->{$field});
        }

        $this->code = mb_strtoupper($this->code);
        $this->cleanLists();

        $validated = $this->validate([
            // The azonosító does not change after creation (07 · 7.2).
            'code' => $this->capability === null
                ? ['required', 'string', 'max:20', 'regex:/^[\p{Lu}0-9]+(?:-[\p{Lu}0-9]+)+$/u', Rule::unique('capabilities', 'code')]
                : [],
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $name, Closure $fail) use ($capabilities): void {
                    if ($capabilities->findByName((string) $name, $this->capability?->id) !== null) {
                        $fail(__('admin.capabilities.name_taken'));
                    }
                },
            ],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('capabilities', 'slug')->ignore($this->capability?->id),
            ],
            'capability_group_id' => ['required', Rule::exists('capability_groups', 'id')],
            'level' => ['required', Rule::enum(CapabilityLevel::class)],
            'status' => ['required', Rule::enum(CapabilityStatus::class)],
            'nature' => ['nullable', Rule::enum(CapabilityNature::class)],
            'tempting' => ['nullable', 'in:0,1'],
            'definition' => ['required', 'string', 'max:2000'],
            'statement' => ['nullable', 'string', 'max:2000'],
            ...$this->listRules('non_examples', maxLength: Capability::ITEM_MAX_LENGTH),
            ...$this->listRules('overlaps', maxLength: Capability::ITEM_MAX_LENGTH),
            ...$this->listRules('keywords'),
            'appearances' => ['array'],
            'appearances.*' => ['nullable', 'string', 'max:5000'],
            'stages' => ['array'],
            'stages.*' => ['nullable', 'string', 'max:2000'],
            'preconditionIds' => ['array'],
            'preconditionIds.*' => [Rule::exists('preconditions', 'id')],
            'dependencyIds' => ['array'],
            'dependencyIds.*' => [Rule::exists('capabilities', 'id')],
            'note' => ['nullable', 'string', 'max:500'],
        ], attributes: $this->validationAttributes());

        $attributes = [
            'name' => $validated['name'],
            'name_en' => filled($validated['name_en']) ? $validated['name_en'] : null,
            'slug' => $validated['slug'],
            'capability_group_id' => (int) $validated['capability_group_id'],
            'level' => CapabilityLevel::from($validated['level']),
            'status' => CapabilityStatus::from($validated['status']),
            'nature' => filled($validated['nature']) ? CapabilityNature::from($validated['nature']) : null,
            'tempting' => filled($validated['tempting']) ? $validated['tempting'] === '1' : null,
            'definition' => $validated['definition'],
            'statement' => filled($validated['statement']) ? $validated['statement'] : null,
            'non_examples' => $validated['non_examples'],
            'overlaps' => $validated['overlaps'],
            'keywords' => $validated['keywords'],
            'appearances' => array_filter(array_map('trim', $validated['appearances']), 'filled'),
            'stages' => array_filter(array_map('trim', $validated['stages']), 'filled'),
        ];
        // In the order of E1–E9.
        $preconditionIds = collect($validated['preconditionIds'])->map(fn ($id) => (int) $id)->sort()->values()->all();
        $dependencyIds = array_map('intval', $validated['dependencyIds']);
        $note = filled($validated['note']) ? $validated['note'] : null;

        if ($this->capability === null) {
            $capability = $capabilities->create(
                ['code' => $validated['code'], ...$attributes],
                array_fill_keys($preconditionIds, null),
                array_fill_keys($dependencyIds, null),
                $note,
            );

            session()->flash('status', __('admin.capabilities.created'));
            $this->redirectRoute('admin.capabilities.edit', $capability, navigate: false);

            return;
        }

        $version = $capabilities->update($this->capability, $attributes, $preconditionIds, $dependencyIds, $note);
        $this->slugLocked = true;
        $this->note = '';

        session()->flash('status', $version === null
            ? __('admin.capabilities.saved_unchanged')
            : __('admin.capabilities.saved_version', ['version' => $version->label()]));
    }

    public function render(): View
    {
        $title = $this->capability?->name ?? __('admin.capabilities.create_title');
        $options = fn (string $enum): array => collect($enum::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all();

        return view('livewire.admin.capabilities.form', [
            'title' => $title,
            'groups' => CapabilityGroup::query()->orderBy('name')->get(),
            'levels' => $options(CapabilityLevel::class),
            'statuses' => $options(CapabilityStatus::class),
            'natures' => $options(CapabilityNature::class),
            'preconditions' => Precondition::query()->orderBy('code')->get(),
            'preconditionNotes' => $this->capability?->preconditions()->get()->pluck('pivot.note', 'id')->filter()->all() ?? [],
            // Archived capabilities are not selectable in a new place, the ones already linked stay (07 · 7.5).
            'dependencyOptions' => Capability::query()
                ->when($this->capability, fn ($query) => $query->whereKeyNot($this->capability->id))
                ->where(fn ($query) => $query->whereNull('archived_at')->orWhereIn('id', $this->dependencyIds))
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
        ])->layout('layouts::admin')->title($title);
    }

    protected function editableLists(): array
    {
        return Capability::LISTS;
    }

    /**
     * @return array<string, string>
     */
    private function validationAttributes(): array
    {
        $attributes = [
            'code' => __('admin.capabilities.fields.code'),
            'name' => __('admin.capabilities.fields.name'),
            'name_en' => __('admin.capabilities.fields.name_en'),
            'capability_group_id' => __('admin.capabilities.fields.group'),
            'level' => __('admin.capabilities.fields.level'),
            'status' => __('admin.capabilities.fields.status'),
            'nature' => __('admin.capabilities.fields.nature'),
            'tempting' => __('admin.capabilities.fields.tempting'),
            'definition' => __('admin.capabilities.fields.definition'),
            'statement' => __('admin.capabilities.fields.statement'),
            'note' => __('admin.capabilities.fields.note'),
            'appearances.*' => __('admin.capabilities.fields.appearances'),
            'stages.*' => __('admin.capabilities.fields.stages'),
            'preconditionIds.*' => __('admin.capabilities.fields.preconditions'),
            'dependencyIds.*' => __('admin.capabilities.fields.dependencies'),
        ];

        foreach (Capability::LISTS as $list) {
            $attributes[$list] = __('admin.capabilities.fields.'.$list);
            $attributes[$list.'.*'] = __('admin.capabilities.fields.'.$list);
        }

        return $attributes;
    }
}
