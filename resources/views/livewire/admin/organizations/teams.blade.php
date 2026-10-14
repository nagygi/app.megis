@php([$sortField, $sortDirection] = $this->currentSort())

<div>
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="__('admin.organizations.tabs.teams')">
        <x-slot:actions>
            <x-ui.button wire:click="create">{{ __('admin.teams.create') }}</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($showForm)
        <form wire:submit="save" class="mb-6 flex max-w-2xl flex-col gap-5 rounded-card border border-grey-250 bg-white p-6">
            <x-form.input name="name" wire:model="name" :label="__('admin.teams.name')" required autofocus />
            <x-form.textarea name="description" wire:model="description" :label="__('admin.teams.description')" rows="3" />
            <div class="flex flex-wrap gap-3">
                <x-ui.button type="submit">{{ __('admin.common.save') }}</x-ui.button>
                <x-ui.button tone="outline" wire:click="cancel">{{ __('admin.common.cancel') }}</x-ui.button>
            </div>
        </form>
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
                    <button type="button" wire:click="edit({{ $team->id }})" class="font-semibold text-charcoal underline">{{ $team->name }}</button>
                </td>
                <td class="px-3 py-2 text-grey-700">{{ $team->description ?? __('admin.common.none') }}</td>
                <td class="px-3 py-2">{{ $team->users_count }}</td>
                <td class="px-3 py-2">{{ $team->leaders->pluck('name')->join(', ') ?: __('admin.common.none') }}</td>
                <td class="px-3 py-2 text-right">
                    <x-admin.row-actions :label="__('admin.common.row_actions', ['name' => $team->name])">
                        <x-admin.row-action wire:click="edit({{ $team->id }})">{{ __('admin.common.edit') }}</x-admin.row-action>
                        <x-admin.row-action :href="route('admin.organizations.members.create', [$organization, 'csapat' => $team->id])">{{ __('admin.teams.invite') }}</x-admin.row-action>
                        <x-admin.row-action wire:click="delete({{ $team->id }})" wire:confirm="{{ __('admin.teams.confirm_delete') }}">{{ __('admin.common.delete') }}</x-admin.row-action>
                    </x-admin.row-actions>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-3 py-6 text-center text-grey-600">{{ __('admin.teams.empty') }}</td></tr>
        @endforelse
    </x-admin.table>
</div>
