@php
    $markers = [];
    $fieldLabel = fn (string $field) => __('admin.patterns.fields.'.$field);
@endphp

<div class="max-w-5xl">
    @include('livewire.admin.patterns.partials.header', ['pattern' => $pattern])

    @include('livewire.admin.partials.version-history')
</div>
