{{-- A field's label; with "info" an info sign beside it explains the field in a bubble (x-ui.info). --}}
@props(['for', 'required' => false, 'info' => null])

@if ($info)
    <span class="flex items-center gap-1.5">
@endif
<label for="{{ $for }}" {{ $attributes->class(['text-sm font-medium text-charcoal']) }}>
    {{ $slot }}
    @if ($required)
        <span class="font-normal text-grey-600">· {{ __('ui.required') }}</span>
    @endif
</label>
@if ($info)
        <x-ui.info :text="$info" />
    </span>
@endif
