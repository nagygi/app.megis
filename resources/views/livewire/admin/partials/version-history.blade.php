{{--
    Version history (03 · 3.3, 07 · 7.3): the list with events, one version
    read only, or two side by side. Expects the ShowsVersionHistory data,
    $fieldLabel (field => label) and $markers (field => [text, tone]).
--}}
@if ($viewed)
    {{-- Read-only view of one version --}}
    <div class="flex flex-col gap-4 rounded-card border border-grey-250 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold">{{ __('admin.versions.readonly', ['version' => $viewed->label()]) }}</h2>
            <x-ui.button tone="outline" size="sm" wire:click="backToList">{{ __('admin.versions.back_to_list') }}</x-ui.button>
        </div>
        <p class="text-sm text-grey-600">{{ $viewed->created_at->format('Y.m.d. H:i') }} · {{ $viewed->creator?->name ?? __('admin.versions.system') }}@if ($viewed->note) · {{ $viewed->note }}@endif</p>

        <dl class="flex flex-col gap-4">
            @foreach ($fields as $field => $type)
                <div class="flex flex-col gap-1">
                    <dt class="flex items-center gap-2 text-sm font-medium">
                        @isset($markers[$field])<x-ui.badge :tone="$markers[$field][1]" aria-hidden="true">{{ $markers[$field][0] }}</x-ui.badge>@endisset
                        {{ $fieldLabel($field) }}
                    </dt>
                    <dd>
                        @if ($type === \App\Services\Versioning\SnapshotComparison::LIST)
                            @if (($viewedSnapshot[$field] ?? []) === [])
                                <span class="text-grey-600">{{ __('admin.versions.empty_list') }}</span>
                            @else
                                <ol class="list-decimal pl-5">
                                    @foreach ($viewedSnapshot[$field] as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ol>
                            @endif
                        @else
                            <span class="whitespace-pre-line">{{ filled($viewedSnapshot[$field] ?? null) ? $viewedSnapshot[$field] : __('admin.common.none') }}</span>
                        @endif
                    </dd>
                </div>
            @endforeach
        </dl>
    </div>
@elseif ($comparison)
    {{-- Side by side comparison --}}
    <div class="flex flex-col gap-4 rounded-card border border-grey-250 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold">{{ __('admin.versions.comparing', ['left' => 'v'.$left, 'right' => 'v'.$right]) }}</h2>
            <x-ui.button tone="outline" size="sm" wire:click="backToList">{{ __('admin.versions.back_to_list') }}</x-ui.button>
        </div>

        <div class="flex flex-wrap gap-3 text-sm" aria-hidden="true">
            <span class="rounded-pill bg-green-soft px-2 py-0.5">+ {{ __('admin.versions.added') }}</span>
            <span class="rounded-pill bg-coral-light px-2 py-0.5 line-through">− {{ __('admin.versions.removed') }}</span>
            <span class="rounded-pill bg-yellow-pale px-2 py-0.5">↕ {{ __('admin.versions.moved') }}</span>
        </div>

        @foreach ($comparison as $row)
            <section @class(['flex flex-col gap-2 rounded-field p-3', 'bg-grey-100' => $row['changed']])>
                <h3 class="flex items-center gap-2 text-sm font-semibold">
                    @isset($markers[$row['field']])<x-ui.badge :tone="$markers[$row['field']][1]" aria-hidden="true">{{ $markers[$row['field']][0] }}</x-ui.badge>@endisset
                    {{ $fieldLabel($row['field']) }}
                </h3>
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach (['old' => 'v'.$left, 'new' => 'v'.$right] as $side => $sideLabel)
                        <div class="flex flex-col gap-1">
                            <span class="text-xs font-semibold uppercase text-grey-600">{{ $sideLabel }}</span>
                            @if ($row['type'] === \App\Services\Versioning\SnapshotComparison::LIST)
                                @if ($row[$side] === [])
                                    <span class="text-grey-600">{{ __('admin.versions.empty_list') }}</span>
                                @else
                                    <ol class="flex flex-col gap-1">
                                        @foreach ($row[$side] as $item)
                                            <li @class([
                                                'rounded-field px-2 py-0.5',
                                                'bg-green-soft' => $item['status'] === 'added',
                                                'bg-coral-light line-through' => $item['status'] === 'removed',
                                                'bg-yellow-pale' => $item['status'] === 'moved',
                                            ])>
                                                @if ($item['status'] !== 'unchanged')
                                                    <span class="sr-only">{{ __('admin.versions.'.$item['status']) }}:</span>
                                                @endif
                                                {{ $item['text'] }}
                                            </li>
                                        @endforeach
                                    </ol>
                                @endif
                            @else
                                <p>
                                    @forelse ($row[$side] as $segment)
                                        @if ($segment['type'] === 'equal')
                                            {{ $segment['text'] }}
                                        @elseif ($segment['type'] === 'insert')
                                            <ins class="rounded-field bg-green-soft no-underline"><span class="sr-only">{{ __('admin.versions.added') }}:</span>{{ $segment['text'] }}</ins>
                                        @else
                                            <del class="rounded-field bg-coral-light"><span class="sr-only">{{ __('admin.versions.removed') }}:</span>{{ $segment['text'] }}</del>
                                        @endif
                                    @empty
                                        <span class="text-grey-600">{{ __('admin.common.none') }}</span>
                                    @endforelse
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
@else
    {{-- Version list and events --}}
    @if ($versions->count() > 1)
        <form wire:submit="compare" class="mb-4 flex flex-wrap items-end gap-3 rounded-card border border-grey-250 bg-white p-4">
            <x-form.select name="left" wire:model="left" :label="__('admin.versions.compare_left')">
                <option value="">–</option>
                @foreach ($versions as $version)
                    <option value="{{ $version->number }}">{{ $version->label() }}</option>
                @endforeach
            </x-form.select>
            <x-form.select name="right" wire:model="right" :label="__('admin.versions.compare_right')">
                <option value="">–</option>
                @foreach ($versions as $version)
                    <option value="{{ $version->number }}">{{ $version->label() }}</option>
                @endforeach
            </x-form.select>
            <x-ui.button type="submit" tone="outline">{{ __('admin.versions.compare_button') }}</x-ui.button>
        </form>
    @endif

    <x-admin.table>
        <x-slot:head>
            <x-admin.th>{{ __('admin.versions.number') }}</x-admin.th>
            <x-admin.th>{{ __('admin.versions.author') }}</x-admin.th>
            <x-admin.th>{{ __('admin.versions.date') }}</x-admin.th>
            <x-admin.th>{{ __('admin.versions.changed') }}</x-admin.th>
            <x-admin.th><span class="sr-only">{{ __('admin.common.actions') }}</span></x-admin.th>
        </x-slot:head>

        @foreach ($timeline as $entry)
            @if ($entry['kind'] === 'version')
                @php($version = $entry['item'])
                <tr wire:key="version-{{ $version->id }}">
                    <td class="px-3 py-2 font-semibold">{{ $version->label() }}</td>
                    <td class="px-3 py-2">{{ $version->creator?->name ?? __('admin.versions.system') }}</td>
                    <td class="px-3 py-2 whitespace-nowrap">{{ $version->created_at->format('Y.m.d. H:i') }}</td>
                    <td class="px-3 py-2 text-grey-700">
                        {{ $version->number === 1
                            ? __('admin.versions.all_fields')
                            : collect($version->changed_fields)->map(fn ($field) => $fieldLabel($field))->implode(', ') }}
                        @if ($version->note)
                            <span class="block text-sm italic">{{ $version->note }}</span>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-right">
                        <x-ui.button tone="outline" size="sm" wire:click="$set('viewing', {{ $version->number }})">{{ __('admin.versions.open') }}</x-ui.button>
                    </td>
                </tr>
            @else
                @php($event = $entry['item'])
                <tr wire:key="event-{{ $event->id }}" class="text-grey-700">
                    <td class="px-3 py-2"><x-ui.badge tone="muted">{{ __('admin.versions.event') }}</x-ui.badge></td>
                    <td class="px-3 py-2">{{ $event->creator?->name ?? __('admin.versions.system') }}</td>
                    <td class="px-3 py-2 whitespace-nowrap">{{ $event->created_at->format('Y.m.d. H:i') }}</td>
                    <td class="px-3 py-2">{{ $event->type->label() }}</td>
                    <td></td>
                </tr>
            @endif
        @endforeach
    </x-admin.table>
@endif
