{{--
    The experience scale (08 · 8.3, 25 · 25.4): 1–6 or "nem tudom". A radio
    group, so it works with keyboards and screen readers without a custom slider.
    The six points share the width (they fit a phone), the ends are named under
    them, and "nem tudom" sits on its own line.
--}}
@props(['name', 'label', 'disabled' => false])

@php
    $pill = 'cursor-pointer rounded-pill border-[1.5px] border-grey-350 bg-white font-semibold transition-colors duration-200 hover:border-charcoal hover:bg-yellow-pale peer-checked:border-charcoal peer-checked:bg-charcoal peer-checked:text-cream peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-coral peer-disabled:cursor-default peer-disabled:hover:border-grey-350 peer-disabled:hover:bg-white';
@endphp

<fieldset {{ $attributes->class(['flex flex-col gap-2']) }} @disabled($disabled)>
    <legend class="sr-only">{{ $label }}</legend>
    <div class="flex max-w-sm flex-col gap-1">
        <div class="grid grid-cols-6 gap-1.5">
            @foreach (['1', '2', '3', '4', '5', '6'] as $point)
                <label class="relative">
                    <input type="radio" name="{{ $name }}" value="{{ $point }}" wire:model.live="{{ $name }}" class="peer sr-only">
                    <span class="{{ $pill }} grid h-11 place-items-center">{{ $point }}</span>
                    <span class="sr-only">{{ __('diagnosis.experience.point', ['point' => $point]) }}</span>
                </label>
            @endforeach
        </div>
        <div class="flex justify-between text-xs text-grey-600" aria-hidden="true">
            <span>{{ __('diagnosis.experience.low') }}</span>
            <span>{{ __('diagnosis.experience.high') }}</span>
        </div>
    </div>
    <label class="relative self-start">
        <input type="radio" name="{{ $name }}" value="dont_know" wire:model.live="{{ $name }}" class="peer sr-only">
        <span class="{{ $pill }} inline-flex min-h-10 items-center px-4 text-sm">{{ __('enums.experience_state.dont_know') }}</span>
    </label>
</fieldset>
