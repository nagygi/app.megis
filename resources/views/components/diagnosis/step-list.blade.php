{{--
    The steps of V1 / T2, the V10 onboarding and the home (23 · 23.1) as the
    prototype's line: a coral ring for the current step, a green tick for a
    done one, a grey ring for the rest, joined by a grey line. Done and
    current steps open. The tail is a last row that does not open (T2:
    Hozzáteszel, while it runs).
--}}
@props(['steps', 'stepUrl', 'open' => true, 'tail' => null])

@php($current = $steps->current())

<ol {{ $attributes->class(['flex flex-col']) }}>
    @foreach ($steps->steps as $state)
        @php($isCurrent = $current !== null && $current->step === $state->step)
        <li>
            <x-diagnosis.step-row
                :state="$state"
                :current="$isCurrent"
                :last="$loop->last && $tail === null"
                :href="$open && ($state->done || $isCurrent) ? $stepUrl($state->step) : null"
            />
        </li>
    @endforeach

    @if ($tail)
        <li class="flex gap-3 text-charcoal">
            <span class="flex shrink-0 flex-col items-center" aria-hidden="true">
                <span class="size-5.5 rounded-pill border-[1.5px] border-grey-500"></span>
            </span>
            <span class="flex min-w-0 flex-1 items-baseline justify-between gap-3 pt-0.5">
                <span class="font-semibold">{{ $tail['title'] }}</span>
                <span class="text-right text-sm text-grey-700">{{ $tail['meta'] }}</span>
            </span>
        </li>
    @endif
</ol>
