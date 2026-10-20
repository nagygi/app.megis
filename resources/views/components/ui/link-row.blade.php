{{--
    A white row that leads somewhere: a title, an optional line under it and
    the chevron. Lists of diagnoses, topics, processes, report pages. The title
    is the slot unless given; "meta" may be a slot for richer lines.
--}}
@props(['href', 'title' => null, 'meta' => null])

<a href="{{ $href }}" {{ $attributes->class(['press flex items-center justify-between gap-3 rounded-card bg-white px-5 py-4 text-charcoal no-underline hover:bg-paper']) }}>
    <span class="min-w-0">
        <span class="block font-semibold">{{ $title ?? $slot }}</span>
        @if ($meta !== null && (string) $meta !== '')
            <span class="block text-sm text-grey-700">{{ $meta }}</span>
        @endif
    </span>
    <x-megis.icon name="chevron-right" class="shrink-0" />
</a>
