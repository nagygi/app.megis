{{-- What the mégis Sheet said about a field: "[JAVASLAT]" or an empty value (07 · 7.6). Expects $capability (or null) and $field. --}}
@if ($capability)
    @php($state = $capability->fieldState($field))
    @if ($capability->isProposed($field) || $state)
        <span class="flex flex-wrap gap-1">
            @if ($capability->isProposed($field))
                <x-ui.badge tone="yellow">{{ __('admin.capabilities.proposed') }}</x-ui.badge>
            @endif
            @if ($state)
                <x-ui.badge tone="muted">{{ __('admin.capabilities.field_states.'.$state) }}</x-ui.badge>
            @endif
        </span>
    @endif
@endif
