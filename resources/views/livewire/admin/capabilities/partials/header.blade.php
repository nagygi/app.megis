{{-- Title, state and tabs of a capability page (07 · 7.1, 7.3, 7.5). Expects $capability. --}}
<div class="mb-6 flex flex-col gap-4">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex flex-col gap-2">
            <h1 class="text-2xl font-semibold text-charcoal">
                <span class="font-mono text-grey-600">{{ $capability->code }}</span> · {{ $capability->name }}
            </h1>
            <div class="flex flex-wrap items-center gap-2 text-sm">
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
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($capability->isArchived())
                <x-ui.button tone="outline" size="sm" wire:click="restore">{{ __('admin.capabilities.restore') }}</x-ui.button>
            @else
                <x-ui.button tone="outline" size="sm" wire:click="archive" wire:confirm="{{ __('admin.capabilities.confirm_archive') }}">{{ __('admin.capabilities.archive') }}</x-ui.button>
            @endif
        </div>
    </div>

    @if ($capability->isArchived())
        <p class="rounded-lg bg-grey-100 px-3 py-2 text-sm">{{ __('admin.capabilities.archived_notice') }}</p>
    @endif

    @if ($capability->imported_at)
        {{-- The Sheet is the master (07, question 1) --}}
        <p class="rounded-lg bg-yellow-pale px-3 py-2 text-sm">{{ __('admin.capabilities.sheet_master', ['date' => $capability->imported_at->format('Y.m.d.')]) }}</p>
    @endif

    <nav aria-label="{{ $capability->name }}">
        <ul class="flex gap-1 border-b border-grey-250">
            @foreach ([['admin.capabilities.edit', 'data'], ['admin.capabilities.versions', 'versions']] as [$route, $tab])
                @php($isActive = request()->routeIs($route))
                <li>
                    <a
                        href="{{ route($route, $capability) }}"
                        @class([
                            'block rounded-t-lg px-4 py-2 text-sm font-semibold no-underline',
                            'bg-white text-charcoal border border-b-0 border-grey-250' => $isActive,
                            'text-grey-700 hover:bg-grey-100' => ! $isActive,
                        ])
                        @if ($isActive) aria-current="page" @endif
                    >{{ __('admin.capabilities.tabs.'.$tab) }}</a>
                </li>
            @endforeach
        </ul>
    </nav>
</div>
