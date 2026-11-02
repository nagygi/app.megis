{{--
    The title of an admin page, with the same build everywhere; the way back
    is in the layout's breadcrumbs:
    - badges: a slot right of the title (state, version);
    - description: a line under the title;
    - actions: a slot on the right.
    spaced=false drops the margin under it when the page's own gap spaces it.
--}}
@props([
    'title',
    'description' => null,
    'spaced' => true,
])

<div {{ $attributes->class(['flex flex-col gap-2', 'mb-6' => $spaced]) }}>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex min-w-0 flex-wrap items-center gap-3">
            <h1 class="text-2xl font-semibold text-charcoal">{{ $title }}</h1>
            @isset($badges)
                {{ $badges }}
            @endisset
        </div>

        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>

    @if ($description)
        <p class="max-w-3xl text-grey-700">{{ $description }}</p>
    @endif
</div>
