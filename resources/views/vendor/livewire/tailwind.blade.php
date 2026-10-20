{{--
    The admin lists' pager (x-admin.table) in the brand: round page buttons,
    the current one filled, chevrons from the icon set, a Hungarian summary.
    Replaces Livewire's stock view, whose grey and blue classes the brand-only
    palette does not have. Paging scrolls back to the top of the list.
--}}
@php
    $scrollTo ??= 'body';
    $scrollIntoView = $scrollTo !== false
        ? "(\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()"
        : '';
    $pageName = $paginator->getPageName();
    $step = 'press grid size-10 place-items-center rounded-pill text-sm font-semibold';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav aria-label="{{ __('pagination.label') }}" class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-grey-700">
                {{ __('pagination.summary', ['first' => $paginator->firstItem(), 'last' => $paginator->lastItem(), 'total' => $paginator->total()]) }}
            </p>

            <ul class="flex flex-wrap items-center gap-1">
                <li>
                    @if ($paginator->onFirstPage())
                        <span class="{{ $step }} text-grey-500" aria-disabled="true">
                            <x-megis.icon name="chevron-left" :label="__('pagination.previous')" />
                        </span>
                    @else
                        <button type="button" wire:click="previousPage('{{ $pageName }}')" x-on:click="{{ $scrollIntoView }}" class="{{ $step }} hover:bg-white">
                            <x-megis.icon name="chevron-left" :label="__('pagination.previous')" />
                        </button>
                    @endif
                </li>

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li><span class="{{ $step }} text-grey-600" aria-hidden="true">{{ $element }}</span></li>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <li wire:key="paginator-{{ $pageName }}-page{{ $page }}">
                                @if ($page == $paginator->currentPage())
                                    <span class="{{ $step }} bg-charcoal text-cream" aria-current="page">{{ $page }}</span>
                                @else
                                    <button type="button" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')" x-on:click="{{ $scrollIntoView }}" class="{{ $step }} hover:bg-white" aria-label="{{ __('pagination.page', ['page' => $page]) }}">{{ $page }}</button>
                                @endif
                            </li>
                        @endforeach
                    @endif
                @endforeach

                <li>
                    @if ($paginator->hasMorePages())
                        <button type="button" wire:click="nextPage('{{ $pageName }}')" x-on:click="{{ $scrollIntoView }}" class="{{ $step }} hover:bg-white">
                            <x-megis.icon name="chevron-right" :label="__('pagination.next')" />
                        </button>
                    @else
                        <span class="{{ $step }} text-grey-500" aria-disabled="true">
                            <x-megis.icon name="chevron-right" :label="__('pagination.next')" />
                        </span>
                    @endif
                </li>
            </ul>
        </nav>
    @endif
</div>
