{{--
    A row of page tabs (links), one look for all of them.
    - tabs: list of ['href' => …, 'label' => …, 'active' => bool]; without
      active, the tab whose href is the page's path, after a Livewire update
      too (whose request goes to livewire/update). An active tab of another
      path is the one the page sits under: aria-current="true", not "page".
      A tab with 'disabled' => true is no link: greyed out with a lock, and its
      'hint' (when it opens) in a bubble. 'group_start' => true draws a thin
      line before the tab, between two groups of tabs;
    - label: the nav's accessible name;
    - variant: underline (default, on light grounds) or folder (in a dark
      header: the active tab takes the page's ground, as if the page were its
      folder).
    Scrolls sideways on a phone instead of wrapping.
--}}
@props(['tabs', 'label', 'variant' => 'underline'])

@php
    $pagePath = trim(\Livewire\Livewire::originalPath(), '/');
    $tabs = array_map(function (array $tab) use ($pagePath) {
        $isPage = ! ($tab['disabled'] ?? false) && trim((string) parse_url((string) ($tab['href'] ?? ''), PHP_URL_PATH), '/') === $pagePath;

        return $tab + ['active' => $isPage, 'current' => $isPage ? 'page' : 'true', 'disabled' => false, 'hint' => null, 'group_start' => false];
    }, $tabs);
@endphp

<nav aria-label="{{ $label }}" {{ $attributes }}>
    <ul @class([
        'flex gap-1 overflow-x-auto',
        'border-b border-grey-250' => $variant === 'underline',
    ])>
        @foreach ($tabs as $tab)
            @if ($tab['group_start'] && ! $loop->first)
                <li aria-hidden="true" @class(['mx-2 my-3 w-px shrink-0', 'bg-grey-250' => $variant === 'underline', 'bg-cream/30' => $variant === 'folder'])></li>
            @endif
            <li class="flex">
                @if ($tab['disabled'])
                    <x-ui.info
                        :text="$tab['hint']"
                        :trigger-class="'flex min-h-11 cursor-help items-center gap-1.5 whitespace-nowrap border-b-2 border-transparent px-4 text-sm font-medium '.($variant === 'folder' ? 'text-cream/60' : 'text-grey-600')"
                    >
                        <x-slot:trigger>
                            <x-megis.icon name="lock" class="size-3.5" />
                            {{ $tab['label'] }}
                        </x-slot:trigger>
                    </x-ui.info>
                @else
                    <a
                        href="{{ $tab['href'] }}"
                        @class([
                            'flex min-h-11 items-center whitespace-nowrap px-4 text-sm font-semibold no-underline transition-colors duration-200',
                            'border-b-2 border-charcoal text-charcoal' => $variant === 'underline' && $tab['active'],
                            'border-b-2 border-transparent text-grey-700 hover:border-grey-300 hover:text-charcoal' => $variant === 'underline' && ! $tab['active'],
                            'rounded-t-field bg-grey-100 text-charcoal' => $variant === 'folder' && $tab['active'],
                            'rounded-t-field text-cream hover:text-yellow' => $variant === 'folder' && ! $tab['active'],
                        ])
                        @if ($tab['active']) aria-current="{{ $tab['current'] }}" @endif
                    >{{ $tab['label'] }}</a>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
