{{--
    Checkbox with its label on the right. Used for consents too, so it is never
    pre-checked by this component: the caller decides with "checked".
--}}
@props([
    'name',
    'label' => null,
    'help' => null,
    'id' => null,
    'value' => '1',
])

@php
    $id ??= str_replace(['.', '[', ']'], ['-', '-', ''], $name);
    $hasError = $errors->has($name);
    $describedBy = collect([$help ? $id.'-help' : null, $hasError ? $id.'-error' : null])->filter()->implode(' ');
@endphp

<div class="flex flex-col gap-1.5">
    <div class="flex items-start gap-3">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="checkbox"
            value="{{ $value }}"
            @if ($hasError) aria-invalid="true" @endif
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->class(['mt-0.5 size-5 shrink-0 rounded border-grey-350 accent-charcoal']) }}
        >
        <label for="{{ $id }}" class="text-base text-charcoal">{{ $label ?? $slot }}</label>
    </div>

    @if ($help)
        <p id="{{ $id }}-help" class="pl-8 text-sm text-grey-600">{{ $help }}</p>
    @endif

    <x-form.error :name="$name" :id="$id" class="pl-8" />
</div>
