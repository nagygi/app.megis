@php
    $markers = ['high_examples' => ['6', 'green'], 'low_examples' => ['1', 'coral']];
    $fieldLabel = fn (string $field) => __('admin.values.fields.'.$field);
@endphp

<div class="max-w-5xl">
    <x-ui.flash class="mb-6" />

    @include('livewire.admin.values.partials.header', ['value' => $value])

    @include('livewire.admin.partials.version-history')
</div>
