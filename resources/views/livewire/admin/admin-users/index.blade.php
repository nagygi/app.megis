@php
    [$sortField, $sortDirection] = $this->currentSort();
    // The same status tones on every user list: clients, members, admins.
    $statusTones = ['active' => 'green', 'added' => 'neutral', 'invited' => 'yellow', 'pending' => 'yellow', 'invitation_expired' => 'coral', 'locked' => 'coral', 'inactive' => 'muted'];
@endphp

<div>
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="__('admin.admins.title')">
        <x-slot:actions>
            <x-ui.button icon="plus" :href="route('admin.admins.create')">{{ __('admin.admins.invite') }}</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.list-toolbar>
        <x-admin.filter name="role" :label="__('admin.admins.role')" :options="collect($roles)->mapWithKeys(fn ($role) => [$role->value => $role->label()])" />
        <x-admin.filter name="status" :label="__('admin.admins.status')" :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])" />
        {{-- Lined up with the 44px fields beside it. --}}
        <div class="flex min-h-11 items-center">
            <x-form.checkbox name="showInactive" wire:model.live="showInactive" :label="__('admin.common.show_inactive')" />
        </div>
    </x-admin.list-toolbar>

    <x-admin.table :paginator="$users">
        <x-slot:head>
            <x-admin.th field="name" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.common.name') }}</x-admin.th>
            <x-admin.th field="email" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.common.email') }}</x-admin.th>
            <x-admin.th field="role" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.admins.role') }}</x-admin.th>
            <x-admin.th>{{ __('admin.admins.status') }}</x-admin.th>
            <x-admin.th field="last_login" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.admins.last_login') }}</x-admin.th>
            <x-admin.th><span class="sr-only">{{ __('admin.common.actions') }}</span></x-admin.th>
        </x-slot:head>

        @forelse ($users as $user)
            @php($status = $user->adminStatus())
            <tr wire:key="admin-{{ $user->id }}">
                <td class="px-3 py-2">
                    @can('updateAdmin', $user)
                        <a href="{{ route('admin.admins.edit', $user) }}" class="font-semibold text-charcoal">{{ $user->name }}</a>
                    @else
                        <span class="font-semibold">{{ $user->name }}</span>
                    @endcan
                </td>
                <td class="px-3 py-2">{{ $user->email }}</td>
                <td class="px-3 py-2"><x-ui.badge :tone="$user->isSuperadmin() ? 'dark' : 'neutral'">{{ $user->role->label() }}</x-ui.badge></td>
                <td class="px-3 py-2"><x-ui.badge :tone="$statusTones[$status->value]">{{ $status->label() }}</x-ui.badge></td>
                <td class="px-3 py-2 whitespace-nowrap">{{ $user->last_login_at?->format('Y.m.d. H:i') ?? __('admin.common.never') }}</td>
                <td class="px-3 py-2 text-right">
                    @canany(['updateAdmin', 'resendInvitation', 'resetPassword', 'deactivate', 'activate'], $user)
                        <x-admin.row-actions :label="__('admin.common.row_actions', ['name' => $user->name])">
                            @can('updateAdmin', $user)
                                <x-admin.row-action :href="route('admin.admins.edit', $user)">{{ __('admin.common.edit') }}</x-admin.row-action>
                            @endcan
                            @can('resendInvitation', $user)
                                <x-admin.row-action wire:click="resendInvitation({{ $user->id }})">{{ __('admin.admins.resend_invitation') }}</x-admin.row-action>
                            @endcan
                            @can('resetPassword', $user)
                                <x-admin.row-action wire:click="resetPassword({{ $user->id }})" wire:confirm="{{ __('admin.admins.confirm_reset') }}">{{ __('admin.admins.reset_password') }}</x-admin.row-action>
                            @endcan
                            @can('deactivate', $user)
                                <x-admin.row-action tone="danger" wire:click="deactivate({{ $user->id }})" wire:confirm="{{ __('admin.admins.confirm_deactivate') }}" data-confirm-tone="danger">{{ __('admin.common.deactivate') }}</x-admin.row-action>
                            @endcan
                            @can('activate', $user)
                                <x-admin.row-action wire:click="activate({{ $user->id }})" wire:confirm="{{ __('admin.admins.confirm_activate') }}">{{ __('admin.common.activate') }}</x-admin.row-action>
                            @endcan
                        </x-admin.row-actions>
                    @endcanany
                </td>
            </tr>
        @empty
            {{-- The signed-in admin is always on the list, so it is empty only under a search or a filter. --}}
            <x-admin.empty-row :colspan="6">{{ __('admin.common.empty') }}</x-admin.empty-row>
        @endforelse
    </x-admin.table>
</div>
