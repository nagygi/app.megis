{{-- Title, state and tabs of a value page (03 · 3.1, 3.3, 3.7). Expects $value. --}}
<div class="mb-6 flex flex-col gap-4">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex flex-col gap-2">
            <h1 class="text-2xl font-semibold text-charcoal">{{ $value->name }}</h1>
            <div class="flex flex-wrap items-center gap-2 text-sm">
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
        </div>

        <div class="flex flex-wrap gap-2">
            @unless ($value->isReviewed())
                <x-ui.button tone="outline" size="sm" wire:click="markReviewed">{{ __('admin.values.mark_reviewed') }}</x-ui.button>
            @endunless
            @if ($value->isArchived())
                <x-ui.button tone="outline" size="sm" wire:click="restore">{{ __('admin.values.restore') }}</x-ui.button>
            @else
                <x-ui.button tone="outline" size="sm" wire:click="archive" wire:confirm="{{ __('admin.values.confirm_archive') }}">{{ __('admin.values.archive') }}</x-ui.button>
            @endif
        </div>
    </div>

    @if ($value->isArchived())
        <p class="rounded-field bg-grey-100 px-3 py-2 text-sm">{{ __('admin.values.archived_notice') }}</p>
    @endif

    @unless ($value->isReviewed())
        <p class="rounded-field bg-yellow-pale px-3 py-2 text-sm">
            {{ $value->originOrganization
                ? __('admin.values.review_notice', ['place' => $value->originOrganization->name])
                : __('admin.values.review_notice_nowhere') }}
        </p>
    @endunless

    <nav aria-label="{{ $value->name }}">
        <ul class="flex gap-1 border-b border-grey-250">
            @foreach ([['admin.values.edit', 'data'], ['admin.values.versions', 'versions']] as [$route, $tab])
                @php($isActive = request()->routeIs($route))
                <li>
                    <a
                        href="{{ route($route, $value) }}"
                        @class([
                            'block rounded-t-field px-4 py-2 text-sm font-semibold no-underline',
                            'bg-white text-charcoal border border-b-0 border-grey-250' => $isActive,
                            'text-grey-700 hover:bg-grey-100' => ! $isActive,
                        ])
                        @if ($isActive) aria-current="page" @endif
                    >{{ __('admin.values.tabs.'.$tab) }}</a>
                </li>
            @endforeach
        </ul>
    </nav>
</div>
