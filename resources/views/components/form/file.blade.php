{{--
    A file picker with label, help (what may be uploaded, NFR · 3) and error.
    The browser's own control, its button drawn as an outline pill.
--}}
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

    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="file"
        @if ($required) required aria-required="true" @endif
        @if ($hasError) aria-invalid="true" @endif
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->class([
            'w-full text-sm text-grey-700',
            'file:mr-3 file:min-h-10 file:cursor-pointer file:rounded-pill file:border-[1.5px] file:border-charcoal file:bg-transparent file:px-4 file:text-sm file:font-semibold file:text-charcoal hover:file:bg-yellow-pale',
            // Disabled as the disabled button: grey, no clicks.
            'disabled:cursor-not-allowed disabled:file:cursor-not-allowed disabled:file:border-grey-300 disabled:file:bg-grey-300 disabled:file:text-grey-600',
        ]) }}
    >

    @if ($help)
        <p id="{{ $id }}-help" class="text-sm text-grey-600">{{ $help }}</p>
    @endif

    <x-form.error :name="$name" :id="$id" />
</div>
