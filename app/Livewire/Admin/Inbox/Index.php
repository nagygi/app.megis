<?php

namespace App\Livewire\Admin\Inbox;

use App\Enums\InboxItemType;
use App\Livewire\Admin\Concerns\WithDataTable;
use App\Models\InboxItem;
use App\Models\Organization;
use App\Models\User;
use App\Services\Inbox\InboxService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Beérkező (22 · 22.1): open rows from every diagnosis; closed rows under
 * "Lezártak". The contact filter starts with the signed-in admin.
 */
class Index extends Component
{
    use WithDataTable;

    public function mount(): void
    {
        Gate::authorize('viewAny', InboxItem::class);
    }

    public function close(int $itemId, InboxService $inbox): void
    {
        $item = InboxItem::query()->findOrFail($itemId);
        Gate::authorize('update', $item);

        $inbox->close($item, auth()->user());
        session()->flash('status', __('admin.inbox.closed_flash'));
    }

    public function render(): View
    {
        $showClosed = $this->filter('state') === 'closed';
        $query = InboxItem::query()
            ->with(['organization', 'diagnosis.team', 'closer'])
            ->when($showClosed, fn ($query) => $query->closed(), fn ($query) => $query->open());

        if ($term = $this->searchTerm()) {
            $query->whereLike('summary', $term);
        }

        if (($type = InboxItemType::tryFrom((string) $this->filter('type'))) !== null) {
            $query->where('type', $type);
        }

        if ($organization = $this->filter('organization')) {
            $query->where('organization_id', $organization);
        }

        match ($contact = $this->filter('contact')) {
            'all' => null,
            null => $query->forContact(auth()->user()),
            default => $query->whereHas('organization', fn ($organization) => $organization->where('contact_admin_id', $contact)),
        };

        return view('livewire.admin.inbox.index', [
            'items' => $this->applySorting($query)->paginate(50),
            'showClosed' => $showClosed,
            'types' => collect(InboxItemType::cases())->mapWithKeys(fn (InboxItemType $type) => [$type->value => $type->label()])->all(),
            'organizations' => Organization::query()->orderBy('name')->pluck('name', 'id')->all(),
            'contacts' => ['all' => __('admin.common.all')] + User::query()->admins()->orderBy('last_name')->get()
                ->mapWithKeys(fn (User $admin) => [$admin->id => $admin->name])->all(),
        ])->layout('layouts::admin')->title(__('admin.inbox.title'));
    }

    protected function sortableColumns(): array
    {
        return [
            'type' => 'type',
            'organization' => Organization::query()->select('name')->whereColumn('organizations.id', 'inbox_items.organization_id'),
            'age' => 'created_at',
            'closed' => 'closed_at',
        ];
    }

    protected function defaultSort(): array
    {
        return ['age', 'asc'];
    }
}
