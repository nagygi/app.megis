{{--
    The Képesség card (R0, R1): coral fire and the hand-drawn coral line.
    The capability pages (R7) are not built yet, so the card says what is
    coming instead of linking. framed: the white R1 block with the coral
    frame, its slot under a line ("Hol kezdenénk").
--}}
@props(['framed' => false])

<section {{ $attributes->class([
    'flex flex-col gap-2 rounded-card p-4',
    'bg-yellow-light' => ! $framed,
    'border-2 border-coral bg-white' => $framed,
]) }} aria-labelledby="capability-card-title">
    <div class="flex items-center gap-2.5">
        <x-megis.symbol name="fire" class="size-5 text-coral" />
        <h2 id="capability-card-title" class="flex-1 text-xs font-semibold tracking-[.08em] uppercase">{{ __('report.capability.title') }}</h2>
        <x-report.soon />
    </div>
    <p class="leading-snug font-semibold text-grey-700">{{ __('report.capability.text') }}</p>
    @if ($framed)
        @if ($slot->isNotEmpty())
            <div class="border-t border-grey-200 pt-2 text-sm">{{ $slot }}</div>
        @endif
    @else
        <span class="-mx-1 mt-0.5 block h-[4.5px] -rotate-[.35deg] bg-coral" aria-hidden="true"></span>
    @endif
</section>
