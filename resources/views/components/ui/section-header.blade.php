{{--
    The heading of a section inside a page: one size for every section title
    (text-lg, semibold), an optional line under it and actions on the right.
    level: 2 (default) or 3; id: for aria-labelledby on the section.
--}}
@props(['title', 'level' => 2, 'description' => null, 'id' => null])

<div {{ $attributes->class(['flex flex-wrap items-end justify-between gap-x-4 gap-y-2']) }}>
    <div class="flex min-w-0 flex-col gap-1">
        <{{ 'h'.$level }} @if ($id) id="{{ $id }}" @endif class="text-lg font-semibold text-charcoal">{{ $title }}</{{ 'h'.$level }}>
        @if ($description)
            <p class="text-sm text-grey-700">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
