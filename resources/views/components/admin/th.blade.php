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
        <button type="button" wire:click="sortBy('{{ $field }}')" class="inline-flex items-center gap-1 uppercase">
            {{ $slot }}
            <span aria-hidden="true" class="text-grey-600">{{ $isSorted ? ($sortDirection === 'asc' ? '▲' : '▼') : '↕' }}</span>
        </button>
    @else
        {{ $slot }}
    @endif
</th>
