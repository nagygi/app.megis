{{--
    The account actions of a user (33 · 33.2, 33.8), inside an x-admin.row-actions.
    Every item is always listed: what the admin may not do is greyed out with the reason.
    $client; $impersonationReason, $statusReason, $archiveReason: null when the action works;
    $withImpersonation: false where "Belépek a nevében" is a button of its own.
--}}
@if ($withImpersonation ?? true)
    @if ($impersonationReason)
        <x-admin.row-action :disabled-reason="$impersonationReason">{{ __('impersonation.start') }}</x-admin.row-action>
    @else
        <form method="POST" action="{{ route('admin.impersonate', $client) }}" target="_blank">
            @csrf
            <x-admin.row-action type="submit">{{ __('impersonation.start') }}</x-admin.row-action>
        </form>
    @endif
@endif

@if ($statusReason)
    <x-admin.row-action :disabled-reason="$statusReason">{{ $client->isActive() ? __('admin.common.deactivate') : __('admin.common.activate') }}</x-admin.row-action>
@elseif ($client->isActive())
    <x-admin.row-action tone="danger" wire:click="deactivate({{ $client->id }})" wire:confirm="{{ __('admin.clients.confirm_deactivate') }}" data-confirm-tone="danger">{{ __('admin.common.deactivate') }}</x-admin.row-action>
@else
    <x-admin.row-action wire:click="activate({{ $client->id }})" wire:confirm="{{ __('admin.clients.confirm_activate') }}">{{ __('admin.common.activate') }}</x-admin.row-action>
@endif

@if ($archiveReason)
    <x-admin.row-action :disabled-reason="$archiveReason">{{ __('admin.clients.archive') }}</x-admin.row-action>
@else
    <x-admin.row-action
        tone="danger"
        wire:click="archive({{ $client->id }})"
        wire:confirm="{{ __('admin.clients.confirm_archive') }}"
        data-confirm-tone="danger"
        :data-confirm-label="__('admin.clients.archive')"
    >{{ __('admin.clients.archive') }}</x-admin.row-action>
@endif
