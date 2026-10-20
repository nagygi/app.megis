@props([
    'name',
    'label',
    'help' => null,
    'required' => false,
    'id' => null,
    'rows' => 4,
])

@php
    $id ??= str_replace(['.', '[', ']'], ['-', '-', ''], $name);
    $maxLength = $attributes->get('maxlength');
    $hasError = $errors->has($name);
    $describedBy = collect([$help ? $id.'-help' : null, $hasError ? $id.'-error' : null])->filter()->implode(' ');
@endphp

<div class="flex flex-col gap-1.5" @if ($maxLength) x-data="{ length: 0 }" x-init="length = $refs.field.value.length" @endif>
    <x-form.label :for="$id" :required="$required">{{ $label }}</x-form.label>

    <textarea
        @if ($maxLength) x-ref="field" x-on:input="length = $el.value.length" @endif
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($required) required aria-required="true" @endif
        @if ($hasError) aria-invalid="true" @endif
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class(['field w-full']) }}
    >{{ $slot }}</textarea>

    @if ($help || $maxLength)
        <div class="flex items-start justify-between gap-3 text-sm text-grey-600">
            @if ($help)
                <p id="{{ $id }}-help">{{ $help }}</p>
            @endif
            @if ($maxLength)
                {{-- Read out only when asked: a live count would talk at every keystroke. --}}
                <p class="ml-auto shrink-0 tabular-nums" x-cloak><span x-text="length"></span> / {{ $maxLength }}</p>
            @endif
        </div>
    @endif

    <x-form.error :name="$name" :id="$id" />
</div>
