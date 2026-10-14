{{-- Validation message for one field. Charcoal text keeps AA contrast; the coral mark signals the error. --}}
@props(['name', 'id'])

@error($name)
    <p id="{{ $id }}-error" {{ $attributes->class(['flex items-start gap-1.5 text-sm text-charcoal']) }}>
        <x-megis.icon name="close" class="mt-0.5 size-3.5 text-coral-dark" />
        <span>{{ $message }}</span>
    </p>
@enderror
