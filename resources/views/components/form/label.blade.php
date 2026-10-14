@props(['for', 'required' => false])

<label for="{{ $for }}" {{ $attributes->class(['text-sm font-medium text-charcoal']) }}>
    {{ $slot }}
    @if ($required)
        <span class="font-normal text-grey-600">· {{ __('ui.required') }}</span>
    @endif
</label>
