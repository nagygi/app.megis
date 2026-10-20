@php
    [$sortField, $sortDirection] = $this->currentSort();
    // An empty list says "Nincs találat." only when the search hid the rows.
    $filtering = filled($search) || collect($filters)->filter(fn ($filter) => filled($filter))->isNotEmpty();
@endphp

<div>
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="__('admin.organizations.tabs.teams')">
        <x-slot:actions>
            <x-ui.button icon="plus" wire:click="create">{{ __('admin.teams.create') }}</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($showForm)
        <x-ui.card as="form" bordered padding="lg" wire:submit="save" class="mb-6 flex max-w-3xl flex-col gap-5">
            <x-form.input name="name" wire:model="name" :label="__('admin.teams.name')" required autofocus />
            <x-form.textarea name="description" wire:model="description" :label="__('admin.teams.description')" rows="3" />
            <div class="flex flex-wrap gap-3">
                <x-ui.button type="submit">{{ __('admin.common.save') }}</x-ui.button>
                <x-ui.button tone="outline" wire:click="cancel">{{ __('admin.common.cancel') }}</x-ui.button>
            </div>
        </x-ui.card>
    @endif

    <x-admin.list-toolbar />

    <x-admin.table :paginator="$teams">
        <x-slot:head>
            <x-admin.th field="name" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.teams.name') }}</x-admin.th>
            <x-admin.th>{{ __('admin.teams.description') }}</x-admin.th>
            <x-admin.th field="users" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.teams.users') }}</x-admin.th>
            <x-admin.th>{{ __('admin.teams.leaders') }}</x-admin.th>
            <x-admin.th><span class="sr-only">{{ __('admin.common.actions') }}</span></x-admin.th>
        </x-slot:head>

        @forelse ($teams as $team)
            <tr wire:key="team-{{ $team->id }}">
                <td class="px-3 py-2">
                    <x-ui.button tone="ghost" size="sm" class="-ml-1.5" wire:click="edit({{ $team->id }})">{{ $team->name }}</x-ui.button>
                </td>
                <td class="px-3 py-2 text-grey-700">{{ $team->description ?? __('admin.common.none') }}</td>
                <td class="px-3 py-2">{{ $team->users_count }}</td>
                <td class="px-3 py-2">{{ $team->leaders->pluck('name')->join(', ') ?: __('admin.common.none') }}</td>
                <td class="px-3 py-2 text-right">
                    <x-admin.row-actions :label="__('admin.common.row_actions', ['name' => $team->name])">
                        <x-admin.row-action wire:click="edit({{ $team->id }})">{{ __('admin.common.edit') }}</x-admin.row-action>
                        <x-admin.row-action :href="route('admin.organizations.members.create', [$organization, 'csapat' => $team->id])">{{ __('admin.teams.invite') }}</x-admin.row-action>
                        <x-admin.row-action tone="danger" wire:click="delete({{ $team->id }})" wire:confirm="{{ __('admin.teams.confirm_delete') }}" data-confirm-tone="danger">{{ __('admin.common.delete') }}</x-admin.row-action>
                    </x-admin.row-actions>
                </td>
            </tr>
        @empty
            <x-admin.empty-row :colspan="5">
                {{ $filtering ? __('admin.common.empty') : __('admin.teams.empty') }}
                @unless ($filtering)
                    <x-slot:action>
                        <x-ui.button tone="outline" icon="plus" wire:click="create">{{ __('admin.teams.create') }}</x-ui.button>
                    </x-slot:action>
                @endunless
            </x-admin.empty-row>
        @endforelse
    </x-admin.table>
</div>
