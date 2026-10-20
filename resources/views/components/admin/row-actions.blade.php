{{-- The menu at the end of an admin list row (NFR · 3): an x-ui.dropdown with the "more" icon. --}}
@props(['label'])

<x-ui.dropdown :label="$label" {{ $attributes }}>
    {{ $slot }}
</x-ui.dropdown>
