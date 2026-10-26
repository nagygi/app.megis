{{--
    A row of the customer menu (M1), cream on the charcoal ground.
    - first level: a symbol, the title and the line under it, divided by a line;
    - nested (inside x-client.menu-group): smaller, with the yellow chevron.
    active marks the current page: yellow title, aria-current.
--}}
@props(['href', 'title', 'meta' => null, 'symbol' => null, 'symbolClass' => 'text-yellow', 'active' => false, 'nested' => false])

<li @class(['border-b border-grey-700' => ! $nested])>
    <a
        href="{{ $href }}"
        @class([
            'press group/row flex min-h-11 items-center gap-3 no-underline [color:inherit]',
            'py-3.5' => ! $nested,
            'py-2' => $nested,
        ])
        @if ($active) aria-current="page" @endif
    >
        @if ($symbol)
            <x-megis.symbol :name="$symbol" class="size-6 {{ $symbolClass }}" />
        @endif
        <span class="min-w-0 flex-1">
            <span @class([
                'block leading-tight font-semibold group-hover/row:text-yellow',
                'text-base' => ! $nested,
                'text-sm' => $nested,
                'text-yellow' => $active,
            ])>{{ $title }}</span>
            @if (filled($meta))
                <span class="mt-0.5 block text-xs text-grey-400">{{ $meta }}</span>
            @endif
        </span>
        @if ($nested)
            <x-megis.icon name="chevron-right" class="shrink-0 text-yellow" />
        @endif
    </a>
</li>
