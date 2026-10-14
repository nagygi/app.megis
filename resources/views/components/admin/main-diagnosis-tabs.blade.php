{{-- Tabs of a main diagnosis (17): the diagnosis, its settings (A1), its subdiagnoses (17 · 17.4). --}}
@props(['diagnosis'])

@php
    $tabs = [
        ['route' => 'admin.diagnoses.show', 'label' => __('admin.main_diagnoses.tabs.overview')],
        ['route' => 'admin.main-diagnoses.edit', 'label' => __('admin.main_diagnoses.tabs.settings')],
        ['route' => 'admin.main-diagnoses.subdiagnoses', 'label' => __('admin.main_diagnoses.tabs.subdiagnoses')],
    ];
@endphp

<nav {{ $attributes->class(['border-b border-grey-250']) }} aria-label="{{ $diagnosis->name }}">
    <ul class="flex gap-1 overflow-x-auto">
        @foreach ($tabs as $tab)
            @php($isActive = request()->routeIs($tab['route']))
            <li>
                <a
                    href="{{ route($tab['route'], $diagnosis) }}"
                    @class([
                        'block whitespace-nowrap border-b-2 px-4 py-2 text-sm font-semibold no-underline',
                        'border-charcoal text-charcoal' => $isActive,
                        'border-transparent text-grey-700 hover:text-charcoal' => ! $isActive,
                    ])
                    @if ($isActive) aria-current="page" @endif
                >{{ $tab['label'] }}</a>
            </li>
        @endforeach
    </ul>
</nav>
