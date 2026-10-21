{{-- What the mégis Sheet said about a field of a pattern (NM-19): "[JAVASLAT]" or an empty value. Expects $item and $field. --}}
@php($state = $item->fieldState($field))
@if ($item->isProposed($field) || $state)
    <span class="inline-flex flex-wrap gap-1 align-middle">
        @if ($item->isProposed($field))
            <x-ui.badge tone="yellow">{{ __('admin.capabilities.proposed') }}</x-ui.badge>
        @endif
        @if ($state)
            <x-ui.badge tone="muted">{{ __('admin.capabilities.field_states.'.$state) }}</x-ui.badge>
        @endif
    </span>
@endif
