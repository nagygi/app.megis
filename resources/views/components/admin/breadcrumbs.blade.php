{{--
    The breadcrumbs of an admin page, above its title (AdminBreadcrumbs::links):
    - trail: the pages above it below the header, each a link; inside an
      organization they start at its tab;
    - current: the page itself, the last crumb, not a link;
    - back: where the page was opened from, when that is not a page of the
      trail (the garden, a client's page): a Vissza before the trail.
    Without a page above, only a Vissza shows. A phone has room for one step
    up: the Vissza, or the crumb right above.
--}}
@props(['trail' => [], 'back' => null, 'current' => null])

@if ($trail !== [] || $back)
    <div {{ $attributes->class(['flex min-w-0 items-center gap-3 text-sm text-grey-700']) }}>
        @if ($back)
            <a id="admin-back" href="{{ $back }}" class="inline-flex min-h-10 shrink-0 items-center gap-1 font-semibold text-charcoal no-underline hover:underline">
                <x-megis.icon name="chevron-left" />
                {{ __('ui.back') }}
            </a>
            @if ($trail !== [])
                <span aria-hidden="true" class="h-4 w-px shrink-0 bg-grey-300 max-sm:hidden"></span>
            @endif
        @endif

        @if ($trail !== [])
            <nav aria-label="{{ __('admin.breadcrumbs') }}" @class(['min-w-0', 'max-sm:hidden' => $back])>
                <ol class="flex min-w-0 flex-wrap items-center gap-x-1.5">
                    @foreach ($trail as $crumb)
                        <li @class(['flex min-w-0 items-center gap-1.5', 'max-sm:hidden' => ! $loop->last])>
                            {{-- The icon's own display wins over a utility: its wrapper hides it. --}}
                            @unless ($loop->first)
                                <span class="flex shrink-0 text-grey-500 max-sm:hidden"><x-megis.icon name="chevron-right" /></span>
                            @endunless
                            @if ($loop->last)
                                <span class="flex shrink-0 sm:hidden"><x-megis.icon name="chevron-left" /></span>
                            @endif
                            <a href="{{ $crumb['url'] }}" class="inline-flex min-h-10 min-w-0 max-w-80 items-center text-grey-700 no-underline hover:text-charcoal hover:underline">
                                <span class="truncate">{{ $crumb['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                    @if (filled($current))
                        <li class="flex min-w-0 items-center gap-1.5 max-sm:hidden">
                            <span class="flex shrink-0 text-grey-500"><x-megis.icon name="chevron-right" /></span>
                            <span aria-current="page" class="min-w-0 max-w-80 truncate text-charcoal">{{ $current }}</span>
                        </li>
                    @endif
                </ol>
            </nav>
        @endif
    </div>
@endif
