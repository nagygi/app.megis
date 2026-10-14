{{--
    The experience scale (08 · 8.3, 25 · 25.4): 1–6 or "nem tudom". A radio
    group, so it works with keyboards and screen readers without a custom slider.
--}}
@props(['name', 'label', 'disabled' => false])

<fieldset {{ $attributes->class(['flex flex-col gap-2']) }} @disabled($disabled)>
    <legend class="sr-only">{{ $label }}</legend>
    <div class="flex flex-wrap items-center gap-1.5">
        @foreach (['1', '2', '3', '4', '5', '6'] as $point)
            <label class="relative">
                <input type="radio" name="{{ $name }}" value="{{ $point }}" wire:model.live="{{ $name }}" class="peer sr-only">
                <span class="grid size-10 cursor-pointer place-items-center rounded-pill border border-grey-350 bg-white font-semibold peer-checked:border-charcoal peer-checked:bg-charcoal peer-checked:text-cream peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-coral peer-disabled:cursor-default">{{ $point }}</span>
                <span class="sr-only">{{ __('diagnosis.experience.point', ['point' => $point]) }}</span>
            </label>
        @endforeach
        <label class="relative ml-1">
            <input type="radio" name="{{ $name }}" value="dont_know" wire:model.live="{{ $name }}" class="peer sr-only">
            <span class="inline-flex h-10 cursor-pointer items-center rounded-pill border border-grey-350 bg-white px-4 text-sm font-semibold peer-checked:border-charcoal peer-checked:bg-charcoal peer-checked:text-cream peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-coral">{{ __('enums.experience_state.dont_know') }}</span>
        </label>
    </div>
    <div class="flex justify-between text-xs text-grey-600" aria-hidden="true">
        <span>{{ __('diagnosis.experience.low') }}</span>
        <span>{{ __('diagnosis.experience.high') }}</span>
    </div>
</fieldset>
