{{-- Title, state and tabs of a capability page (07 · 7.1, 7.3, 7.5). Expects $capability. --}}
<div class="mb-6 flex flex-col gap-4">
    <x-admin.page-header :title="$capability->code.' · '.$capability->name" :spaced="false">
        <x-slot:badges>
            <div class="flex flex-wrap items-center gap-2">
                @if ($capability->latestVersion)
                    <x-ui.badge tone="dark">{{ $capability->latestVersion->label() }}</x-ui.badge>
                @endif
                @if ($capability->isArchived())
                    <x-ui.badge tone="muted">{{ __('admin.capabilities.archived') }}</x-ui.badge>
                @else
                    <x-ui.badge tone="green">{{ __('admin.capabilities.active') }}</x-ui.badge>
                @endif
                <x-ui.badge>{{ $capability->status->label() }}</x-ui.badge>
            </div>
        </x-slot:badges>

        <x-slot:actions>
            @if ($capability->isArchived())
                <x-ui.button tone="outline" size="sm" wire:click="restore">{{ __('admin.capabilities.restore') }}</x-ui.button>
            @else
                <x-ui.button
                    tone="danger"
                    size="sm"
                    wire:click="archive"
                    wire:confirm="{{ __('admin.capabilities.confirm_archive') }}"
                    data-confirm-tone="danger"
                    :data-confirm-label="__('admin.capabilities.archive')"
                >{{ __('admin.capabilities.archive') }}</x-ui.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    @if ($capability->isArchived())
        <x-ui.notice>{{ __('admin.capabilities.archived_notice') }}</x-ui.notice>
    @endif

    @if ($capability->imported_at)
        {{-- The Sheet is the master (07, question 1): an edit here lasts until the next import. --}}
        <x-ui.notice tone="warning">{{ __('admin.capabilities.sheet_master', ['date' => $capability->imported_at->format('Y.m.d.')]) }}</x-ui.notice>
    @endif

    <x-ui.tabs
        :label="$capability->name"
        :tabs="collect(['admin.capabilities.edit' => 'data', 'admin.capabilities.versions' => 'versions'])->map(fn (string $tab, string $route) => [
            'href' => route($route, $capability),
            'label' => __('admin.capabilities.tabs.'.$tab),
            'active' => request()->routeIs($route),
        ])->values()->all()"
    />
</div>
