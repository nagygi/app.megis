@php
    $markers = [];
    $fieldLabel = fn (string $field) => __('admin.patterns.fields.'.$field);
@endphp

<div>
    @include('livewire.admin.patterns.partials.header', ['pattern' => $pattern])

    @include('livewire.admin.partials.version-history')
</div>
