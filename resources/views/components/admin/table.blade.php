{{-- Admin list table. Pass the header cells in "head" and the rows in the slot. --}}
@props(['paginator' => null])

<div {{ $attributes->class(['overflow-x-auto rounded-card border border-grey-250 bg-white']) }}>
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
