{{-- One row of the step line (23 · 23.1); a link when the step can be opened. The last row has no line down. --}}
@props(['state', 'current' => false, 'last' => false, 'href' => null])

@php
    $status = $state->done ? __('diagnosis.step_status.done') : ($current ? __('diagnosis.step_status.current') : __('diagnosis.step_status.todo'));
    $classes = 'group flex gap-3 text-charcoal no-underline';
@endphp

@if ($href)
    <a href="{{ $href }}" class="{{ $classes }}" @if ($current) aria-current="step" @endif>
        @include('components.diagnosis.partials.step-row-content')
    </a>
@else
    <div class="{{ $classes }}">
        @include('components.diagnosis.partials.step-row-content')
    </div>
@endif
