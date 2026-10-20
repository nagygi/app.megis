{{--
    Text-like input with label, explanatory help text and validation message (NFR · 3).
    "name" is the validation key; for Livewire pass wire:model with the same path.
--}}
@props([
    'name',
    'label',
    'help' => null,
    'type' => 'text',
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

    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($required) required aria-required="true" @endif
        @if ($hasError) aria-invalid="true" @endif
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class(['field w-full']) }}
    >

    @if ($help)
        <p id="{{ $id }}-help" class="text-sm text-grey-600">{{ $help }}</p>
    @endif

    <x-form.error :name="$name" :id="$id" />
</div>
