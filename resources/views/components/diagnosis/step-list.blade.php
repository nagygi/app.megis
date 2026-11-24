{{--
    The steps of V1 / T2, the V10 onboarding and the home (23 · 23.1) as the
    prototype's line: a coral ring for the current step, a green tick for a
    done one, a grey ring for the rest, joined by a grey line. Done and
    current steps open. The tail is a last row that does not open (T2:
    Hozzáteszel, while it runs). The head is a first row before the steps
    (the home of an invited member: the Értéktükör, 26 · 26.4); while it is
    there, it is where the user stands, and no step below is the current one.
    head: array{title: string, minutes: int, done: bool, lead: ?string, action: ?array{label: string, href: string, tone: string}}
--}}
@props(['steps', 'stepUrl', 'open' => true, 'tail' => null, 'head' => null])

@php($current = $head === null ? $steps->current() : null)

<ol {{ $attributes->class(['flex flex-col']) }}>
    @if ($head)
        <li class="flex gap-3 text-charcoal" @unless ($head['done']) aria-current="step" @endunless>
            <span class="flex shrink-0 flex-col items-center" aria-hidden="true">
                @if ($head['done'])
                    <span class="grid size-5.5 place-items-center rounded-pill bg-green text-cream"><x-megis.icon name="check" class="size-3" /></span>
                @else
                    <span class="grid size-5.5 place-items-center rounded-pill border-2 border-coral"><span class="size-2.5 rounded-pill bg-coral"></span></span>
                @endif
                <span class="mt-[3px] min-h-3.5 w-0.5 flex-1 bg-grey-250"></span>
            </span>
            <span class="flex min-h-11 min-w-0 flex-1 flex-col items-start gap-2 pt-0.5 pb-3.5">
                <span class="flex w-full items-baseline justify-between gap-3">
                    <span class="min-w-0">
                        <span class="block font-semibold">{{ $head['title'] }}</span>
                        @unless ($head['done'])
                            <span class="block text-sm font-semibold text-coral-ink">{{ __('client.home.mirror_step.here') }}</span>
                        @endunless
                    </span>
                    <span class="shrink-0 whitespace-nowrap text-sm text-grey-700">{{ __('diagnosis.minutes', ['minutes' => $head['minutes']]) }}</span>
                    <span class="sr-only">· {{ $head['done'] ? __('diagnosis.step_status.done') : __('diagnosis.step_status.current') }}</span>
                </span>
                @if ($head['lead'])
                    <span class="text-sm text-grey-700">{{ $head['lead'] }}</span>
                @endif
                @if ($head['action'])
                    <x-ui.button :tone="$head['action']['tone']" size="sm" icon-right="arrow-right" :href="$head['action']['href']">{{ $head['action']['label'] }}</x-ui.button>
                @endif
            </span>
        </li>
    @endif

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
