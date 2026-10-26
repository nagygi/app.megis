{{-- Tárak → Értéktár (30 · 30.1, 30.2, 30.4; prototype M2): rows divided by a line, no cards. --}}
<div class="flex max-w-3xl flex-col gap-10">
    <x-ui.flash />

    @if ($valueSet)
        {{-- 30.2 · the organization's values; for a private person they are the own values --}}
        <section class="flex flex-col gap-1" aria-labelledby="organization-values">
            <div class="flex flex-wrap items-center justify-between gap-x-3">
                <h2 id="organization-values" class="text-xs font-medium tracking-wider text-green uppercase">
                    {{ $organization->isIndividual() ? __('client.value_library.own_values') : __('client.value_library.organization_values') }}
                </h2>
                @if ($organizationEditUrl)
                    <x-ui.button tone="ghost" size="sm" :href="$organizationEditUrl">{{ __('client.value_library.edit') }}</x-ui.button>
                @endif
            </div>
            @if ($report && $scores->isNotEmpty())
                <p class="text-sm text-grey-700">{{ collect([
                    __('client.value_library.scores_from', ['date' => $report->published_at?->format('m.d.')]),
                    $olderDefinition ? __('client.value_library.older_definition', ['date' => $report->published_at?->format('m.d.')]) : null,
                ])->filter()->implode(' ') }}</p>
            @endif

            <ul class="flex flex-col divide-y divide-grey-200">
                @foreach ($valueSet->items as $item)
                    @php
                        $isOpen = $openItemKey === $item->key;
                        $score = $report ? $scores->get(mb_strtolower(trim($item->name))) : null;
                    @endphp
                    <li wire:key="item-{{ $item->key }}" class="flex flex-col gap-2 py-3.5">
                        <div class="flex items-start gap-3">
                            <div class="flex min-w-0 flex-1 flex-col gap-1 break-words">
                                <h3 class="font-semibold">{{ $item->name }}</h3>
                                @if ($item->meaning)
                                    <p class="text-sm">{{ $item->meaning }}</p>
                                @endif
                                <p class="text-sm text-grey-700">{{ __('client.value_library.low') }}: {{ $item->low_example }} {{ __('client.value_library.high') }}: {{ $item->high_example }}</p>
                                <x-ui.button tone="ghost" size="sm" class="-ml-1.5 self-start" :icon-right="$isOpen ? 'chevron-up' : 'chevron-down'" wire:click="toggleItem('{{ $item->key }}')" aria-expanded="{{ $isOpen ? 'true' : 'false' }}">
                                    {{ $isOpen ? __('client.value_library.hide') : __('client.value_library.show') }}
                                </x-ui.button>
                            </div>
                            @if ($score)
                                {{-- The score of the latest report, to the value's chapter page (27 · R2·b); a low one in coral, as in M2. --}}
                                @php($isLow = ($score['score_state'] ?? null) === 'scored' && $score['score'] <= 2)
                                <a
                                    href="{{ route('client.report.item', [$organization, $report->diagnosis, 'ertekek', $score['id']]) }}"
                                    @class([
                                        'press -mr-1 flex min-h-11 min-w-11 shrink-0 items-center justify-end gap-1 pl-2 no-underline',
                                        'text-coral-ink' => $isLow,
                                        'text-charcoal' => ! $isLow,
                                    ])
                                >
                                    <span class="text-base font-extrabold tabular-nums">{{ \App\Services\Report\ReportText::score($score) }}</span>
                                    <x-megis.icon name="chevron-right" class="text-grey-600" />
                                    <span class="sr-only">{{ $item->name }} · {{ __('client.value_library.score_from', ['date' => $report->published_at?->format('m.d.')]) }}</span>
                                </a>
                            @endif
                        </div>
                        @if ($isOpen)
                            <dl class="grid gap-2 pb-1 text-sm sm:grid-cols-[12rem_1fr]">
                                @foreach (\App\Models\OrganizationValueSetItem::TEXT_FIELDS as $field)
                                    <dt class="font-semibold">{{ __('client.value_library.fields.'.$field) }}</dt>
                                    <dd>{{ $item->{$field} ?: '–' }}</dd>
                                @endforeach
                            </dl>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($ownItems->isNotEmpty())
        {{-- 30.3 · the own values with their state (24 · 24.4) --}}
        <section class="flex flex-col gap-1" aria-labelledby="own-values">
            <div class="flex flex-wrap items-center justify-between gap-x-3">
                <h2 id="own-values" class="text-xs font-medium tracking-wider text-green uppercase">{{ __('client.value_library.own_values') }}</h2>
                @if ($ownEditUrl)
                    <x-ui.button tone="ghost" size="sm" :href="$ownEditUrl">{{ __('client.value_library.edit') }}</x-ui.button>
                @endif
            </div>
            <ul class="flex flex-col divide-y divide-grey-200">
                @foreach ($ownItems as $ownItem)
                    @php($state = $ownState($ownItem))
                    <li wire:key="own-{{ $ownItem->key }}" class="flex items-start gap-3 py-3.5">
                        <div class="min-w-0 flex-1 break-words">
                            <span class="block font-semibold">{{ $ownItem->name }}</span>
                            @if ($ownItem->meaning)
                                <span class="mt-0.5 block text-sm">{{ $ownItem->meaning }}</span>
                            @endif
                        </div>
                        <x-ui.badge :tone="['strong' => 'green', 'partial' => 'yellow', 'none' => 'muted'][$state] ?? 'muted'" class="shrink-0">{{ __('diagnosis.own_values.state.'.$state) }}</x-ui.badge>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- 30.4 · the mégis Értéktár, read-only --}}
    <section class="flex flex-col gap-3" aria-labelledby="library">
        <div class="flex flex-col gap-1">
            <h2 id="library" class="text-xs font-medium tracking-wider text-green uppercase">{{ __('client.value_library.library') }}</h2>
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
            <ul class="flex flex-col divide-y divide-grey-200">
                @foreach ($library as $value)
                    @php($isOpen = $openValueId === $value->id)
                    <li wire:key="value-{{ $value->id }}">
                        <x-ui.list-row
                            :expanded="$isOpen"
                            :title="$value->name"
                            :meta="filled($value->definition) ? $value->definition : ($value->meanings[0] ?? null)"
                            wire:click="toggleValue({{ $value->id }})"
                        />
                        @if ($isOpen)
                            <dl class="grid gap-2 pb-3 text-sm sm:grid-cols-[12rem_1fr]">
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
                            <div class="pb-4">
                                <x-ui.button tone="outline" size="sm" icon="plus" wire:click="addToOwn({{ $value->id }})">{{ __('client.value_library.add_to_own') }}</x-ui.button>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
