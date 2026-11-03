{{--
    The versions of an organization's value set (08 · 8.4, 8.5), on its
    "Értékek" and "A márkád alapja" tabs alike: one set, one list. "Megnyitom"
    sets the page's viewing, which shows that tab's part read-only.
--}}
<section class="flex flex-col gap-3" aria-labelledby="versions-title">
    <h2 id="versions-title" class="text-lg font-semibold">{{ __('admin.values.tabs.versions') }}</h2>
    @if ($versions->isEmpty())
        <x-ui.empty-state class="border border-grey-250">{{ __('admin.value_sets.no_versions') }}</x-ui.empty-state>
    @else
        <x-admin.table>
            <x-slot:head>
                <x-admin.th>{{ __('admin.versions.number') }}</x-admin.th>
                <x-admin.th>{{ __('admin.versions.author') }}</x-admin.th>
                <x-admin.th>{{ __('admin.versions.date') }}</x-admin.th>
                <x-admin.th><span class="sr-only">{{ __('admin.common.actions') }}</span></x-admin.th>
            </x-slot:head>
            @foreach ($versions as $version)
                <tr wire:key="set-version-{{ $version->id }}">
                    <td class="px-3 py-2 font-semibold">{{ $version->label() }}</td>
                    <td class="px-3 py-2">{{ $version->creator?->name ?? __('admin.versions.system') }}</td>
                    <td class="px-3 py-2 whitespace-nowrap">{{ $version->created_at->format('Y.m.d. H:i') }}</td>
                    <td class="px-3 py-2 text-right">
                        <x-ui.button tone="outline" size="sm" wire:click="$set('viewing', {{ $version->number }})">{{ __('admin.versions.open') }}</x-ui.button>
                    </td>
                </tr>
            @endforeach
        </x-admin.table>
    @endif
</section>
