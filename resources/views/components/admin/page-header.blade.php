{{-- Page title with optional actions on the right. --}}
@props(['title'])

<div {{ $attributes->class(['mb-6 flex flex-wrap items-center justify-between gap-4']) }}>
    <h1 class="text-2xl font-semibold text-charcoal">{{ $title }}</h1>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
