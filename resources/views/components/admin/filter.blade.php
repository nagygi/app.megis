{{--
    A filter on one column's values. $options: value => label. The empty choice
    is "Mind" unless the list has another default, e.g. "Aktív".
--}}
@props(['name', 'label', 'options', 'allLabel' => null])

<div class="flex flex-col gap-1.5">
    <label for="filter-{{ $name }}" class="text-sm font-medium">{{ $label }}</label>
    <select
        id="filter-{{ $name }}"
        wire:model.live="filters.{{ $name }}"
        class="field"
    >
        <option value="">{{ $allLabel ?? __('admin.common.all') }}</option>
        @foreach ($options as $value => $optionLabel)
            <option value="{{ $value }}">{{ $optionLabel }}</option>
        @endforeach
    </select>
</div>
