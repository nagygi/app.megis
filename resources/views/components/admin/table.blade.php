{{--
    Admin list table. Pass the header cells in "head" and the rows in the slot.
    The wrapper scrolls a wide table sideways; it is positioned so the sr-only
    labels inside scroll with it instead of widening the page on a phone.
--}}
@props(['paginator' => null])

<div {{ $attributes->class(['relative overflow-x-auto rounded-card border border-grey-250 bg-white']) }}>
    <table class="w-full text-left text-sm">
        <thead class="border-b border-grey-250 bg-paper">
            <tr>{{ $head }}</tr>
        </thead>
        <tbody class="divide-y divide-grey-200">
            {{ $slot }}
        </tbody>
    </table>
</div>

@if ($paginator?->hasPages())
    <div class="mt-4">{{ $paginator->links() }}</div>
@endif
