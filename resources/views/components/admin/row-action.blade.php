{{-- One item of a row-actions menu: an x-ui.dropdown-item (link, button, danger, disabled with a reason). --}}
@props(['href' => null, 'type' => 'button', 'tone' => 'default', 'disabledReason' => null])

<x-ui.dropdown-item :href="$href" :type="$type" :tone="$tone" :disabled-reason="$disabledReason" {{ $attributes }}>{{ $slot }}</x-ui.dropdown-item>
