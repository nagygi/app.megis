<?php

namespace App\Livewire\Admin\Organizations;

use App\Enums\OrganizationType;
use App\Livewire\Admin\Concerns\WithDataTable;
use App\Models\Organization;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Szervezetek (02 · 2.2).
 */
class Index extends Component
{
    use WithDataTable;

    public function mount(): void
    {
        Gate::authorize('viewAny', Organization::class);
    }

    public function deactivate(int $organizationId): void
    {
        $organization = Organization::query()->findOrFail($organizationId);
        Gate::authorize('update', $organization);

        $organization->forceFill(['deactivated_at' => now()])->save();
        session()->flash('status', __('admin.common.saved'));
    }

    public function activate(int $organizationId): void
    {
        $organization = Organization::query()->findOrFail($organizationId);
        Gate::authorize('update', $organization);

        $organization->forceFill(['deactivated_at' => null])->save();
        session()->flash('status', __('admin.common.saved'));
    }

    public function render(): View
    {
        $query = Organization::query()
            ->with(['product', 'contactAdmin'])
            ->withCount(['memberships as memberships_counting_count' => fn (Builder $memberships) => $memberships->countingTowardsLimit()]);

        if ($term = $this->searchTerm()) {
            $query->where(fn (Builder $search) => $search->whereLike('name', $term)->orWhereLike('slug', $term));
        }

        if ($type = OrganizationType::tryFrom((string) $this->filter('type'))) {
            $query->where('type', $type);
        }

        if ($productId = $this->filter('product')) {
            $query->where('product_id', $productId);
        }

        if ($contactId = $this->filter('contact')) {
            $query->where('contact_admin_id', $contactId);
        }

        match ($this->filter('status')) {
            'active' => $query->whereNull('deactivated_at'),
            'inactive' => $query->whereNotNull('deactivated_at'),
            default => null,
        };

        return view('livewire.admin.organizations.index', [
            'organizations' => $this->applySorting($query)->paginate(25),
            'typeOptions' => collect(OrganizationType::cases())->mapWithKeys(fn (OrganizationType $type) => [$type->value => $type->label()]),
            'productOptions' => Product::query()->orderBy('name')->pluck('name', 'id'),
            'contactOptions' => User::query()->admins()->orderBy('last_name')->get()->mapWithKeys(fn (User $admin) => [$admin->id => $admin->name]),
            'statusOptions' => ['active' => __('admin.organizations.active'), 'inactive' => __('admin.organizations.inactive')],
        ])->layout('layouts::admin')->title(__('admin.organizations.title'));
    }

    protected function sortableColumns(): array
    {
        return [
            'name' => 'name',
            'type' => 'type',
            'product' => Product::query()->select('name')->whereColumn('products.id', 'organizations.product_id'),
            'users' => 'memberships_counting_count',
            'contact' => User::query()->select('last_name')->whereColumn('users.id', 'organizations.contact_admin_id'),
            'status' => 'deactivated_at',
        ];
    }

    protected function defaultSort(): array
    {
        return ['name', 'asc'];
    }
}
