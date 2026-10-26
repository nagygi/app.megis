@php
    [$sortField, $sortDirection] = $this->currentSort();
    $accountTones = ['claimed' => 'green', 'awaiting_confirmation' => 'yellow', 'awaiting_login' => 'yellow', 'anonymous' => 'muted'];
    // An empty list says "Nincs találat." only when a search or a filter hid the rows.
    $filtering = filled($search) || collect($filters)->filter(fn ($filter) => filled($filter))->isNotEmpty();
@endphp

<div>
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="__('admin.mirrors.title')" :description="__('admin.mirrors.description')" />

    <x-admin.list-toolbar :search-label="__('admin.mirrors.search')">
        <x-admin.filter name="account" :label="__('admin.mirrors.account')" :options="$accountOptions" />
        <x-admin.filter name="status" :label="__('admin.mirrors.status')" :options="$statusOptions" />
        <x-admin.filter name="source" :label="__('admin.mirrors.source')" :options="$sourceOptions" />
    </x-admin.list-toolbar>

    <x-admin.table :paginator="$mirrors">
        <x-slot:head>
            <x-admin.th field="created" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.mirrors.created') }}</x-admin.th>
            <x-admin.th>{{ __('admin.mirrors.who') }}</x-admin.th>
            <x-admin.th>{{ __('admin.mirrors.source') }}</x-admin.th>
            <x-admin.th>{{ __('admin.mirrors.organization') }}</x-admin.th>
            <x-admin.th>{{ __('admin.mirrors.status') }}</x-admin.th>
            <x-admin.th>{{ __('admin.mirrors.account') }}</x-admin.th>
            <x-admin.th field="expires" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('admin.mirrors.expires') }}</x-admin.th>
            <x-admin.th><span class="sr-only">{{ __('admin.common.actions') }}</span></x-admin.th>
        </x-slot:head>

        @forelse ($mirrors as $mirror)
            @php($account = $mirror->accountState())
            @php($who = $mirror->user?->name ?? $mirror->email ?? __('admin.mirrors.someone'))
            <tr wire:key="mirror-{{ $mirror->id }}">
                <td class="px-3 py-2 whitespace-nowrap">{{ $mirror->created_at->format('Y.m.d. H:i') }}</td>
                <td class="px-3 py-2">
                    <a href="{{ route('admin.mirrors.show', $mirror) }}" class="font-semibold text-charcoal">{{ $who }}</a>
                </td>
                <td class="px-3 py-2">{{ $mirror->material?->context->label() ?? __('admin.common.none') }}</td>
                <td class="px-3 py-2">{{ $mirror->organization?->name ?? __('admin.common.none') }}</td>
                <td class="px-3 py-2">
                    <x-ui.badge :tone="$mirror->status === \App\Enums\MirrorStatus::Failed ? 'coral' : ($mirror->status->isDone() ? 'green' : 'yellow')">{{ $mirror->status->label() }}</x-ui.badge>
                </td>
                <td class="px-3 py-2"><x-ui.badge :tone="$accountTones[$account]">{{ __('admin.mirrors.account_states.'.$account) }}</x-ui.badge></td>
                <td class="px-3 py-2 whitespace-nowrap text-grey-700">
                    @if ($mirror->isAnonymous() && $mirror->expires_at)
                        <span title="{{ $mirror->expires_at->format('Y.m.d. H:i') }}">{{ $mirror->isExpired() ? __('admin.mirrors.expired') : $mirror->expires_at->diffForHumans() }}</span>
                    @else
                        {{ __('admin.common.none') }}
                    @endif
                </td>
                <td class="px-3 py-2 text-right">
                    <x-admin.row-actions :label="__('admin.common.row_actions', ['name' => $who])">
                        <x-admin.row-action :href="route('admin.mirrors.show', $mirror)">{{ __('admin.mirrors.open') }}</x-admin.row-action>
                        <x-admin.row-action tone="danger" wire:click="delete({{ $mirror->id }})" wire:confirm="{{ __($mirror->diagnosis_id ? 'admin.mirrors.confirm_delete_keeps_diagnosis' : 'admin.mirrors.confirm_delete') }}" data-confirm-tone="danger">{{ __('admin.common.delete') }}</x-admin.row-action>
                    </x-admin.row-actions>
                </td>
            </tr>
        @empty
            <x-admin.empty-row :colspan="8">{{ $filtering ? __('admin.common.empty') : __('admin.mirrors.empty') }}</x-admin.empty-row>
        @endforelse
    </x-admin.table>
</div>
