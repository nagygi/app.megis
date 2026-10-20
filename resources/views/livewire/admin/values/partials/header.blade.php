{{-- Title, state and tabs of a value page (03 · 3.1, 3.3, 3.7). Expects $value. --}}
<div class="mb-6 flex flex-col gap-4">
    <x-admin.page-header :title="$value->name" :spaced="false">
        <x-slot:badges>
            <div class="flex flex-wrap items-center gap-2">
                @if ($value->latestVersion)
                    <x-ui.badge tone="dark">{{ $value->latestVersion->label() }}</x-ui.badge>
                @endif
                @if ($value->isArchived())
                    <x-ui.badge tone="muted">{{ __('admin.values.archived') }}</x-ui.badge>
                @else
                    <x-ui.badge tone="green">{{ __('admin.values.active') }}</x-ui.badge>
                @endif
                @if ($value->origin->needsReview())
                    <x-ui.badge>{{ $value->origin->label() }}</x-ui.badge>
                @endif
            </div>
        </x-slot:badges>

        <x-slot:actions>
            @unless ($value->isReviewed())
                <x-ui.button tone="outline" size="sm" wire:click="markReviewed">{{ __('admin.values.mark_reviewed') }}</x-ui.button>
            @endunless
            @if ($value->isArchived())
                <x-ui.button tone="outline" size="sm" wire:click="restore">{{ __('admin.values.restore') }}</x-ui.button>
            @else
                <x-ui.button
                    tone="danger"
                    size="sm"
                    wire:click="archive"
                    wire:confirm="{{ __('admin.values.confirm_archive') }}"
                    data-confirm-tone="danger"
                    :data-confirm-label="__('admin.values.archive')"
                >{{ __('admin.values.archive') }}</x-ui.button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    @if ($value->isArchived())
        <x-ui.notice>{{ __('admin.values.archived_notice') }}</x-ui.notice>
    @endif

    @unless ($value->isReviewed())
        <x-ui.notice tone="warning">
            {{ $value->originOrganization
                ? __('admin.values.review_notice', ['place' => $value->originOrganization->name])
                : __('admin.values.review_notice_nowhere') }}
        </x-ui.notice>
    @endunless

    <x-ui.tabs
        :label="$value->name"
        :tabs="collect(['admin.values.edit' => 'data', 'admin.values.versions' => 'versions'])->map(fn (string $tab, string $route) => [
            'href' => route($route, $value),
            'label' => __('admin.values.tabs.'.$tab),
            'active' => request()->routeIs($route),
        ])->values()->all()"
    />
</div>
