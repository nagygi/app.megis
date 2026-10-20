{{-- Tárak → Értéktár (30 · 30.1, 30.2, 30.4). --}}
<div class="flex max-w-3xl flex-col gap-8">
    <x-ui.flash />

    @if ($valueSet)
        {{-- 30.2 · the organization's values; for a private person they are the own values --}}
        <section class="flex flex-col gap-3" aria-labelledby="organization-values">
            <div class="flex flex-col gap-1">
                <h2 id="organization-values" class="text-xl font-semibold">
                    {{ $organization->isIndividual() ? __('client.value_library.own_values') : __('client.value_library.organization_values') }}
                </h2>
                <p class="text-sm text-grey-700">{{ __('client.value_library.set_header', [
                    'count' => $valueSet->items->count(),
                    'organization' => $organization->name,
                    'date' => ($valueSet->latestVersion?->created_at ?? $valueSet->updated_at)->format('Y.m.d.'),
                ]) }}</p>
                @if ($report && $olderDefinition)
                    <p class="text-sm text-grey-700">{{ __('client.value_library.older_definition', ['date' => $report->published_at?->format('m.d.')]) }}</p>
                @endif
                @if ($organizationEditUrl)
                    <x-ui.button tone="outline" size="sm" class="mt-1 self-start" :href="$organizationEditUrl">{{ __('client.value_library.edit') }}</x-ui.button>
                @endif
            </div>

            <ul class="flex flex-col gap-3">
                @foreach ($valueSet->items as $item)
                    @php($isOpen = $openItemKey === $item->key)
                    <x-ui.card as="li" wire:key="item-{{ $item->key }}" class="flex flex-col gap-2">
                        {{-- On a phone the text takes the full width, the score and "Részletek" go below it. --}}
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex min-w-0 flex-col gap-1 break-words">
                                <h3 class="text-lg font-semibold">{{ $item->name }}</h3>
                                @if ($item->meaning)
                                    <p>{{ $item->meaning }}</p>
                                @endif
                                <p class="text-sm text-grey-700">{{ __('client.value_library.low') }}: {{ $item->low_example }} {{ __('client.value_library.high') }}: {{ $item->high_example }}</p>
                            </div>
                            <div class="flex shrink-0 items-start justify-between gap-3">
                                @php($score = $scores->get(mb_strtolower(trim($item->name))))
                                @if ($score && $report)
                                    <a href="{{ route('client.report.item', [$organization, $report->diagnosis, 'ertekek', $score['id']]) }}" class="text-charcoal no-underline sm:text-right">
                                        <span class="flex items-center gap-1 text-2xl font-extrabold tabular-nums sm:justify-end">{{ \App\Services\Report\ReportText::score($score) }} <x-megis.icon name="chevron-right" /></span>
                                        <span class="block text-xs text-grey-700">{{ __('client.value_library.score_from', ['date' => $report->published_at?->format('m.d.')]) }}</span>
                                    </a>
                                @endif
                                <x-ui.button tone="ghost" size="sm" :icon-right="$isOpen ? 'chevron-up' : 'chevron-down'" wire:click="toggleItem('{{ $item->key }}')" aria-expanded="{{ $isOpen ? 'true' : 'false' }}">
                                    {{ $isOpen ? __('client.value_library.hide') : __('client.value_library.show') }}
                                </x-ui.button>
                            </div>
                        </div>
                        @if ($isOpen)
                            <dl class="grid gap-2 border-t border-grey-200 pt-3 text-sm sm:grid-cols-[12rem_1fr]">
                                @foreach (\App\Models\OrganizationValueSetItem::TEXT_FIELDS as $field)
                                    <dt class="font-semibold">{{ __('client.value_library.fields.'.$field) }}</dt>
                                    <dd>{{ $item->{$field} ?: '–' }}</dd>
                                @endforeach
                            </dl>
                        @endif
                    </x-ui.card>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($ownItems->isNotEmpty())
        {{-- 30.3 · the own values with their state (24 · 24.4) --}}
        <section class="flex flex-col gap-3" aria-labelledby="own-values">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="own-values" class="text-xl font-semibold">{{ __('client.value_library.own_values') }}</h2>
                @if ($ownEditUrl)
                    <x-ui.button tone="outline" size="sm" :href="$ownEditUrl">{{ __('client.value_library.edit') }}</x-ui.button>
                @endif
            </div>
            <ul class="grid gap-3 sm:grid-cols-2">
                @foreach ($ownItems as $ownItem)
                    @php($state = $ownState($ownItem))
                    <x-ui.card as="li" padding="sm" wire:key="own-{{ $ownItem->key }}" class="flex flex-col items-start gap-1">
                        <span class="font-semibold">{{ $ownItem->name }}</span>
                        @if ($ownItem->meaning)
                            <span class="text-sm">{{ $ownItem->meaning }}</span>
                        @endif
                        <x-ui.badge :tone="['strong' => 'green', 'partial' => 'yellow', 'none' => 'muted'][$state] ?? 'muted'" class="mt-1">{{ __('diagnosis.own_values.state.'.$state) }}</x-ui.badge>
                    </x-ui.card>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- 30.4 · the mégis Értéktár, read-only --}}
    <section class="flex flex-col gap-4" aria-labelledby="library">
        <div class="flex flex-col gap-1">
            <h2 id="library" class="text-xl font-semibold">{{ __('client.value_library.library') }}</h2>
            <p class="text-sm text-grey-700">{{ __('client.value_library.library_lead', ['count' => $libraryCount]) }}</p>
        </div>

        <div class="flex flex-wrap items-end gap-3">
            <div class="flex min-w-56 flex-1 flex-col gap-1.5">
                <label for="library-search" class="text-sm font-medium">{{ __('client.value_library.search') }}</label>
                <div class="relative">
                    <x-megis.icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-grey-600" />
                    <input id="library-search" type="search" wire:model.live.debounce.300ms="search" class="field w-full pl-10">
                </div>
            </div>
            @if ($categories->isNotEmpty())
                <div class="flex flex-col gap-1.5">
                    <label for="library-category" class="text-sm font-medium">{{ __('client.value_library.category') }}</label>
                    <select id="library-category" wire:model.live="category" class="field">
                        <option value="">{{ __('client.value_library.all_categories') }}</option>
                        @foreach ($categories as $categoryOption)
                            <option value="{{ $categoryOption->id }}">{{ $categoryOption->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        @if ($library->isEmpty())
            <x-ui.empty-state>{{ __('client.value_library.empty') }}</x-ui.empty-state>
        @else
            <ul class="flex flex-col gap-2">
                @foreach ($library as $value)
                    @php($isOpen = $openValueId === $value->id)
                    <x-ui.card as="li" padding="none" wire:key="value-{{ $value->id }}">
                        <button type="button" wire:click="toggleValue({{ $value->id }})" class="flex w-full items-center justify-between gap-3 rounded-card px-5 py-4 text-left hover:bg-paper" aria-expanded="{{ $isOpen ? 'true' : 'false' }}">
                            <span>
                                <span class="block font-semibold">{{ $value->name }}</span>
                                @if (filled($value->definition))
                                    <span class="block text-sm text-grey-700">{{ $value->definition }}</span>
                                @elseif ($value->meanings !== [])
                                    <span class="block text-sm text-grey-700">{{ $value->meanings[0] }}</span>
                                @endif
                            </span>
                            <x-megis.icon name="chevron-down" :class="'shrink-0 transition-transform duration-200'.($isOpen ? ' rotate-180' : '')" />
                        </button>
                        @if ($isOpen)
                            <dl class="grid gap-2 border-t border-grey-200 px-5 py-4 text-sm sm:grid-cols-[12rem_1fr]">
                                @foreach ([
                                    'meanings' => 'meaning',
                                    'non_meanings' => 'non_meaning',
                                    'low_examples' => 'low_example',
                                    'high_examples' => 'high_example',
                                    'examples' => 'examples',
                                ] as $list => $label)
                                    @if ($value->{$list} !== [])
                                        <dt class="font-semibold">{{ __('client.value_library.fields.'.$label) }}</dt>
                                        <dd>
                                            <ul class="flex flex-col gap-0.5">
                                                @foreach ($value->{$list} as $line)
                                                    <li>{{ $line }}</li>
                                                @endforeach
                                            </ul>
                                        </dd>
                                    @endif
                                @endforeach
                            </dl>
                            <div class="border-t border-grey-200 px-5 py-3">
                                <x-ui.button tone="outline" size="sm" icon="plus" wire:click="addToOwn({{ $value->id }})">{{ __('client.value_library.add_to_own') }}</x-ui.button>
                            </div>
                        @endif
                    </x-ui.card>
                @endforeach
            </ul>
        @endif
    </section>
</div>
