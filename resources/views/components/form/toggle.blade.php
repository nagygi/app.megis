{{--
    On/off switch with an explanation of what "on" does (NFR · 3).
    A native checkbox under the hood, so it works with wire:model and keyboards.
--}}
@props([
    'name',
    'label',
    'help' => null,
    'id' => null,
])

@php
    $id ??= str_replace(['.', '[', ']'], ['-', '-', ''], $name);
@endphp

<div class="flex flex-col gap-1.5">
    <label for="{{ $id }}" class="inline-flex cursor-pointer items-center gap-3">
        <span class="relative inline-flex">
            <input
                id="{{ $id }}"
                name="{{ $name }}"
                type="checkbox"
                role="switch"
                value="1"
                @if ($help) aria-describedby="{{ $id }}-help" @endif
                {{ $attributes->class(['peer sr-only']) }}
            >
            <span class="h-6 w-11 rounded-pill bg-grey-350 transition-colors peer-checked:bg-green peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-coral"></span>
            <span class="absolute top-0.5 left-0.5 size-5 rounded-pill bg-white transition-transform peer-checked:translate-x-5"></span>
        </span>
        <span class="text-base font-medium text-charcoal">{{ $label }}</span>
    </label>

    @if ($help)
        <p id="{{ $id }}-help" class="text-sm text-grey-600">{{ $help }}</p>
    @endif

    <x-form.error :name="$name" :id="$id" />
</div>
