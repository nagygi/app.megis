{{--
    Left menu of the admin; the app version sits at the bottom (NFR · 2, 01 · 1.5),
    under it the on-page tickets switch (exot/onsite-comment, local and staging only).
--}}
@php
    // The layout resolves where the page sits ($location): its menu item is lit, its group kept open.
    $groups = app(\App\Support\AdminNavigation::class)->groups(auth()->user());

    // Items inside a labelled group are sub-items: smaller and greyer than the top-level ones.
    $linkClasses = fn (bool $isActive, bool $nested): string => 'flex min-h-10 items-center justify-between gap-2 rounded-field px-3 py-2 no-underline '
        .($nested ? 'text-sm ' : '')
        .($isActive ? 'bg-charcoal font-semibold text-cream' : ($nested ? 'text-grey-700 hover:bg-grey-100 hover:text-charcoal' : 'text-charcoal hover:bg-grey-100'));
@endphp

<div class="flex min-h-full flex-col gap-6 p-4">
    <div class="flex items-center justify-between gap-2">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex px-3 pt-2 no-underline">
            <x-megis.logo class="h-9" />
        </a>
        <button type="button" class="press grid size-11 place-items-center rounded-pill hover:bg-grey-100 lg:hidden" @click="navOpen = false">
            <x-megis.icon name="close" class="size-5" :label="__('admin.menu_close')" />
        </button>
    </div>

    <nav aria-label="{{ __('admin.title') }}" class="flex flex-1 flex-col gap-6">
        @foreach ($groups as $group)
            @php($groupActive = collect($group['items'])->contains('route', $location->section))
            <div
                @if ($group['label'])
                    x-data="{
                        key: 'admin-nav:{{ $group['items'][0]['route'] }}',
                        open: true,
                        init() {
                            try { if (localStorage.getItem(this.key) === 'closed') this.open = {{ $groupActive ? 'true' : 'false' }}; } catch (e) {}
                        },
                        toggle() {
                            this.open = ! this.open;
                            try { localStorage.setItem(this.key, this.open ? 'open' : 'closed'); } catch (e) {}
                        },
                    }"
                @endif
            >
                @if ($group['label'])
                    <button
                        type="button"
                        class="flex min-h-9 w-full items-center justify-between gap-2 rounded-field px-3 text-left text-xs font-semibold tracking-wide text-charcoal uppercase hover:bg-grey-100"
                        aria-controls="admin-nav-group-{{ $loop->index }}"
                        :aria-expanded="open"
                        @click="toggle()"
                    >
                        <span>{{ $group['label'] }}</span>
                        <x-megis.icon name="chevron-down" class="transition-transform" ::class="open || '-rotate-90'" />
                    </button>
                @endif
                <ul
                    id="admin-nav-group-{{ $loop->index }}"
                    @class([
                        'flex flex-col',
                        'gap-1' => ! $group['label'],
                        'mt-1 ml-3 gap-0.5 border-l-2 border-grey-200 pl-2' => $group['label'],
                    ])
                    @if ($group['label']) x-show="open" @endif
                >
                    @foreach ($group['items'] as $item)
                        @php($isActive = $item['route'] === $location->section)
                        @php($count = isset($item['count']) ? $item['count']() : 0)
                        <li>
                            <a
                                href="{{ route($item['route']) }}"
                                class="{{ $linkClasses($isActive, (bool) $group['label']) }}"
                                @if ($isActive) aria-current="page" @endif
                            >
                                <span>{{ $item['label'] }}</span>
                                @if ($count > 0)
                                    <span @class([
                                        'rounded-pill px-2 text-xs font-semibold',
                                        'bg-cream text-charcoal' => $isActive,
                                        'bg-coral text-black' => ! $isActive,
                                    ])>{{ $count }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    <div class="px-3 text-xs text-grey-600">
        <p>{{ __('admin.version', ['version' => config('megis.version')]) }}</p>
        @onsiteCommentAdminLink(request()->fullUrl(), 'mt-1 inline-block text-grey-700 hover:text-charcoal')
    </div>
</div>
