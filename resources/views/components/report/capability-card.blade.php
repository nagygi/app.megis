{{--
    The Képesség card (R0, R1, V12): coral fire and the hand-drawn coral line.
    With an overview of approved capabilities (NM-12) it lists what to build
    on and what is missing, and links to the Képességtár; without one it says
    what is coming. The capability pages (R7) are not built yet. framed: the
    white R1 block with the coral frame, its slot under a line ("Hol kezdenénk").
--}}
@use('App\Services\Capabilities\CapabilityText')
@props(['framed' => false, 'overview' => null, 'href' => null])

@php($live = $overview !== null && ($overview['strengths'] !== [] || $overview['gaps'] !== []))

<section {{ $attributes->class([
    'flex flex-col gap-2 rounded-card p-4',
    'bg-yellow-light' => ! $framed,
    'border-2 border-coral bg-white' => $framed,
]) }} aria-labelledby="capability-card-title">
    <div class="flex items-center gap-2.5">
        <x-megis.symbol name="fire" class="size-5 text-coral" />
        <h2 id="capability-card-title" class="flex-1 text-xs font-semibold tracking-[.08em] uppercase">{{ __('report.capability.title') }}</h2>
        @unless ($live)
            <x-report.soon />
        @endunless
    </div>
    @if ($live)
        @foreach (['strengths', 'gaps'] as $list)
            @if ($overview[$list] !== [])
                <div class="flex flex-col">
                    <p class="text-sm font-semibold text-grey-700">{{ __('capabilities.card.'.$list) }}</p>
                    <ul class="flex flex-col">
                        @foreach ($overview[$list] as $row)
                            <li class="flex items-baseline justify-between gap-3 border-b border-grey-200 py-1.5 last:border-b-0">
                                <span class="leading-snug font-semibold">{{ $row['name'] }}</span>
                                <span class="shrink-0 text-sm text-grey-700">{{ CapabilityText::confidence($row) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
        <p class="text-xs text-grey-700">{{ __('capabilities.card.maturity_later') }}</p>
        @if ($href)
            <x-ui.button tone="ghost" size="sm" icon-right="chevron-right" :href="$href" class="-ml-3 self-start">{{ __('capabilities.card.library') }}</x-ui.button>
        @endif
    @else
        <p class="leading-snug font-semibold text-grey-700">{{ __('report.capability.text') }}</p>
    @endif
    @if ($framed)
        @if ($slot->isNotEmpty())
            <div class="border-t border-grey-200 pt-2 text-sm">{{ $slot }}</div>
        @endif
    @else
        <span class="-mx-1 mt-0.5 block h-[4.5px] -rotate-[.35deg] bg-coral" aria-hidden="true"></span>
    @endif
</section>
