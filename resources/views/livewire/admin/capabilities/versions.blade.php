@php
    $markers = [];
    $fieldLabel = fn (string $field) => __('admin.capabilities.fields.'.$field);
@endphp

<div>
    <x-ui.flash class="mb-6" />

    @include('livewire.admin.capabilities.partials.header', ['capability' => $capability])

    @include('livewire.admin.partials.version-history')
</div>
