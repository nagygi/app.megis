{{--
    The steps of V1 / T2 (23 · 23.1): coral dot for the current step, a tick
    for a done one, grey for the rest. Done and current steps open.
--}}
@props(['steps', 'stepUrl', 'open' => true])

@php($current = $steps->current())

<ol {{ $attributes->class(['flex flex-col gap-2']) }}>
    @foreach ($steps->steps as $state)
        @php($isCurrent = $current !== null && $current->step === $state->step)
        <li>
            <x-diagnosis.step-row
                :state="$state"
                :number="$loop->iteration"
                :current="$isCurrent"
                :href="$open && ($state->done || $isCurrent) ? $stepUrl($state->step) : null"
            />
        </li>
    @endforeach
</ol>
