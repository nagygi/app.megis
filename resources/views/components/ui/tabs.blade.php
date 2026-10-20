{{--
    A row of page tabs (links), one look for all of them.
    - tabs: list of ['href' => …, 'label' => …, 'active' => bool];
    - label: the nav's accessible name;
    - variant: underline (default, on light grounds) or folder (in a dark
      header: the active tab takes the page's ground, as if the page were its
      folder).
    Scrolls sideways on a phone instead of wrapping.
--}}
@props(['tabs', 'label', 'variant' => 'underline'])

<nav aria-label="{{ $label }}" {{ $attributes }}>
    <ul @class([
        'flex gap-1 overflow-x-auto',
        'border-b border-grey-250' => $variant === 'underline',
    ])>
        @foreach ($tabs as $tab)
            <li>
                <a
                    href="{{ $tab['href'] }}"
                    @class([
                        'flex min-h-11 items-center whitespace-nowrap px-4 text-sm font-semibold no-underline transition-colors duration-200',
                        'border-b-2 border-charcoal text-charcoal' => $variant === 'underline' && $tab['active'],
                        'border-b-2 border-transparent text-grey-700 hover:border-grey-300 hover:text-charcoal' => $variant === 'underline' && ! $tab['active'],
                        'rounded-t-field bg-grey-100 text-charcoal' => $variant === 'folder' && $tab['active'],
                        'rounded-t-field text-cream hover:text-yellow' => $variant === 'folder' && ! $tab['active'],
                    ])
                    @if ($tab['active']) aria-current="page" @endif
                >{{ $tab['label'] }}</a>
            </li>
        @endforeach
    </ul>
</nav>
