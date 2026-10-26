{{--
    The foot of a report page (prototype .chf): the Coach (not built yet),
    where the reader is ("2 / 8 · Értékek", underlined in coral) and the one
    primary action, the next page.
--}}
@props(['status' => null, 'next' => null, 'nextLabel' => null])

<nav {{ $attributes->class(['flex items-center justify-between gap-2.5 border-t border-grey-250 pt-3.5']) }} aria-label="{{ __('report.footer.label') }}">
    <span class="grid size-9 shrink-0 place-items-center rounded-pill" title="{{ __('report.coach.name') }} · {{ __('report.soon') }}">
        <x-megis.symbol name="spiral" class="size-6 text-grey-500" />
        <span class="sr-only">{{ __('report.coach.name') }} · {{ __('report.soon') }}</span>
    </span>
    <span class="min-w-0 flex-1">
        @if ($status)
            <span class="border-b-[2.5px] border-coral pb-0.5 text-xs font-semibold">{{ $status }}</span>
        @endif
    </span>
    @if ($next)
        <x-ui.button size="sm" icon-right="arrow-right" :href="$next">{{ $nextLabel }}</x-ui.button>
    @endif
</nav>
