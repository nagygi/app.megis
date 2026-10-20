{{-- Native select; pass <option> elements as the slot. --}}
@props([
    'name',
    'label',
    'help' => null,
    'required' => false,
    'id' => null,
])

@php
    $id ??= str_replace(['.', '[', ']'], ['-', '-', ''], $name);
    $hasError = $errors->has($name);
    $describedBy = collect([$help ? $id.'-help' : null, $hasError ? $id.'-error' : null])->filter()->implode(' ');
@endphp

<div class="flex flex-col gap-1.5">
    <x-form.label :for="$id" :required="$required">{{ $label }}</x-form.label>

    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if ($required) required aria-required="true" @endif
        @if ($hasError) aria-invalid="true" @endif
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class([
            'w-full rounded-field border bg-white px-3.5 py-2.5 text-base text-charcoal',
            'border-coral-dark' => $hasError,
            'border-grey-350 focus:border-charcoal' => ! $hasError,
        ]) }}
    >
        {{ $slot }}
    </select>

    @if ($help)
        <p id="{{ $id }}-help" class="text-sm text-grey-600">{{ $help }}</p>
    @endif

    <x-form.error :name="$name" :id="$id" />
</div>
