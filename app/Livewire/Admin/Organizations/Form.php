<?php

namespace App\Livewire\Admin\Organizations;

use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\Product;
use App\Models\User;
use App\Services\Organizations\SlugGenerator;
use App\Support\ReservedSlugs;
use Closure;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * New organization, and the "Adatok" tab of an existing one (02 · 2.3).
 */
class Form extends Component
{
    public ?Organization $organization = null;

    public string $type = OrganizationType::Organization->value;

    public string $name = '';

    public string $slug = '';

    /**
     * The slug follows the name until someone unlocks it with "Szerkesztés".
     */
    public bool $slugLocked = true;

    public string $description = '';

    public ?int $contact_admin_id = null;

    public ?int $product_id = null;

    public ?int $headcount_limit = null;

    public function mount(?Organization $organization = null): void
    {
        if ($organization?->exists) {
            Gate::authorize('update', $organization);

            $this->organization = $organization;
            $this->type = $organization->type->value;
            $this->name = $organization->name;
            $this->slug = $organization->slug;
            $this->description = (string) $organization->description;
            $this->contact_admin_id = $organization->contact_admin_id;
            $this->product_id = $organization->product_id;
            $this->headcount_limit = $organization->headcount_limit;

            return;
        }

        Gate::authorize('create', Organization::class);
    }

    public function updatedName(SlugGenerator $slugs): void
    {
        if ($this->organization === null && $this->slugLocked) {
            $this->slug = $slugs->fromName($this->name);
        }
    }

    public function unlockSlug(): void
    {
        $this->slugLocked = false;
    }

    public function save(ReservedSlugs $reservedSlugs): void
    {
        $this->organization === null
            ? Gate::authorize('create', Organization::class)
            : Gate::authorize('update', $this->organization);

        $isIndividual = $this->type === OrganizationType::Individual->value;

        $validated = $this->validate([
            'type' => ['required', Rule::enum(OrganizationType::class)],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('organizations', 'slug')->ignore($this->organization?->id),
                function (string $attribute, mixed $value, Closure $fail) use ($reservedSlugs): void {
                    if ($reservedSlugs->contains((string) $value)) {
                        $fail(__('admin.organizations.slug_reserved'));
                    }
                },
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'contact_admin_id' => ['nullable', Rule::exists('users', 'id')->whereNotNull('role')->whereNull('deactivated_at')],
            'product_id' => ['required', Rule::exists('products', 'id')],
            'headcount_limit' => [
                'nullable',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, Closure $fail) use ($isIndividual): void {
                    $currentHeadcount = $this->organization?->headcount() ?? 0;

                    if (! $isIndividual && $value !== null && (int) $value < $currentHeadcount) {
                        $fail(__('admin.organizations.headcount_limit_too_low', ['count' => $currentHeadcount]));
                    }
                },
            ],
        ]);

        $attributes = [
            'name' => trim($validated['name']),
            'slug' => $validated['slug'],
            'description' => filled($validated['description']) ? $validated['description'] : null,
            'contact_admin_id' => $validated['contact_admin_id'],
            'product_id' => $validated['product_id'],
            'headcount_limit' => $isIndividual ? null : $validated['headcount_limit'],
        ];

        if ($this->organization === null) {
            // The type is chosen once, at creation (02 · 2.3).
            $organization = Organization::create(['type' => $validated['type'], ...$attributes]);

            session()->flash('status', __('admin.organizations.created'));
            $this->redirectRoute('admin.organizations.members', $organization, navigate: false);

            return;
        }

        $this->organization->update($attributes);
        $this->slugLocked = true;

        session()->flash('status', __('admin.common.saved'));
    }

    public function render(): View
    {
        $title = $this->organization?->name ?? __('admin.organizations.create_title');

        return view('livewire.admin.organizations.form', [
            'isEditing' => $this->organization !== null,
            'typeOptions' => OrganizationType::cases(),
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
            'contacts' => User::query()->admins()->whereNull('deactivated_at')->orderBy('last_name')->get(),
            'slugChanged' => $this->organization !== null && $this->slug !== $this->organization->slug,
        ])->layout('layouts::admin', ['organization' => $this->organization])->title($title);
    }
}
