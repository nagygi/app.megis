{{--
    Table header cell. With a "field" it becomes a sort button wired to the
    WithDataTable trait (NFR · 3: every admin column is sortable).
--}}
@props([
    'field' => null,
    'sortField' => '',
    'sortDirection' => 'asc',
])

@php
    $isSorted = $field !== null && $sortField === $field;
    $ariaSort = $isSorted ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none';
@endphp

<th
    scope="col"
    @if ($field) aria-sort="{{ $ariaSort }}" @endif
    {{ $attributes->class(['px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-grey-700']) }}
>
    @if ($field)
        <button type="button" wire:click="sortBy('{{ $field }}')" class="-mx-1 inline-flex min-h-8 items-center gap-1 rounded-field px-1 uppercase hover:text-charcoal">
            {{ $slot }}
            <x-megis.icon :name="$isSorted ? ($sortDirection === 'asc' ? 'chevron-up' : 'chevron-down') : 'sort'" :class="$isSorted ? 'text-charcoal' : 'text-grey-600'" />
        </button>
    @else
        {{ $slot }}
    @endif
</th>
