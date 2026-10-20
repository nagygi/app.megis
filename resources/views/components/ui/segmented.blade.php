{{--
    A small group of pills of which one is chosen: recording or typing, the
    report's Egyén / Csapat / Szervezet, a list's view. The items are
    x-ui.segment; label names the group.
--}}
@props(['label'])

<div role="group" aria-label="{{ $label }}" {{ $attributes->class(['flex flex-wrap gap-2']) }}>
    {{ $slot }}
</div>
