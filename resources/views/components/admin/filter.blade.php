{{-- A filter on one column's values. $options: value => label. --}}
@props(['name', 'label', 'options'])

<div class="flex flex-col gap-1.5">
    <label for="filter-{{ $name }}" class="text-sm font-medium">{{ $label }}</label>
    <select
        id="filter-{{ $name }}"
        wire:model.live="filters.{{ $name }}"
        class="rounded-xl border border-grey-350 bg-white px-3 py-2 text-base focus:border-charcoal"
    >
        <option value="">{{ __('admin.common.all') }}</option>
        @foreach ($options as $value => $optionLabel)
            <option value="{{ $value }}">{{ $optionLabel }}</option>
        @endforeach
    </select>
</div>
