{{--
    The state of a row on the running home (prototype V10): a green tick for
    done, a coral dot for now, a grey ring for later; the word for screen readers.
--}}
@props(['state'])

<span {{ $attributes->class(['grid size-6 shrink-0 place-items-center rounded-pill']) }}>
    @if ($state === 'done')
        <span class="grid size-6 place-items-center rounded-pill bg-green text-cream" aria-hidden="true"><x-megis.icon name="check" class="size-3.5" /></span>
    @elseif ($state === 'now')
        <span class="size-3 rounded-pill bg-coral" aria-hidden="true"></span>
    @else
        <span class="size-3 rounded-pill border-2 border-grey-350" aria-hidden="true"></span>
    @endif
    <span class="sr-only">{{ __('diagnosis.home.states.'.$state) }}</span>
</span>
