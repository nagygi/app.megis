{{-- One row of the step list (23 · 23.1); a link when the step can be opened. --}}
@props(['state', 'number', 'current' => false, 'href' => null])

@php
    $status = $state->done ? __('diagnosis.step_status.done') : ($current ? __('diagnosis.step_status.current') : __('diagnosis.step_status.todo'));
    $classes = 'flex items-center gap-3 rounded-card px-5 py-4 text-charcoal no-underline '.($href ? 'press border border-grey-250 bg-white hover:bg-paper' : 'bg-grey-100');
@endphp

@if ($href)
    <a href="{{ $href }}" class="{{ $classes }}" @if ($current) aria-current="step" @endif>
        @include('components.diagnosis.partials.step-row-content')
        <x-megis.icon name="chevron-right" class="shrink-0" />
    </a>
@else
    <div class="{{ $classes }}">
        @include('components.diagnosis.partials.step-row-content')
    </div>
@endif
