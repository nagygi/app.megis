@php
    $fieldMarkers = ['low_example' => ['1', 'coral'], 'high_example' => ['6', 'green']];
    $requiredFields = ['low_example', 'high_example'];
@endphp

<div class="flex max-w-3xl flex-col gap-6">
    <x-admin.page-header :title="__('admin.organizations.tabs.values')" :spaced="false" />
    <x-ui.flash />

    @if ($viewed)
        {{-- A saved version, read-only (08 · 8.5) --}}
        <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-4">
            <x-ui.section-header :title="__('admin.versions.readonly', ['version' => $viewed->label()])">
                <x-slot:actions>
                    <x-ui.button tone="outline" size="sm" icon="chevron-left" wire:click="$set('viewing', null)">{{ __('admin.versions.back_to_list') }}</x-ui.button>
                </x-slot:actions>
            </x-ui.section-header>
            <p class="text-sm text-grey-700">{{ $viewed->created_at->format('Y.m.d. H:i') }} · {{ $viewed->creator?->name ?? __('admin.versions.system') }}</p>

            @foreach ($viewed->snapshot['values'] ?? [] as $item)
                <div class="rounded-field bg-paper p-4">
                    <h3 class="font-semibold">{{ $item['name'] }}</h3>
                    <dl class="mt-2 grid gap-2 text-sm md:grid-cols-[14rem_1fr]">
                        @foreach (\App\Models\OrganizationValueSetItem::TEXT_FIELDS as $field)
                            <dt class="font-medium">{{ __('admin.value_sets.fields.'.$field) }}</dt>
                            <dd>{{ filled($item[$field] ?? null) ? $item[$field] : __('admin.common.none') }}</dd>
                        @endforeach
                    </dl>
                </div>
            @endforeach

            <dl class="grid gap-2 text-sm md:grid-cols-[14rem_1fr]">
                @foreach (\App\Models\OrganizationValueSet::BRAND_FIELDS as $field)
                    <dt class="font-medium">{{ __('admin.value_sets.brand.'.$field) }}</dt>
                    <dd>{{ filled($viewed->snapshot[$field] ?? null) ? $viewed->snapshot[$field] : __('admin.common.none') }}</dd>
                @endforeach
            </dl>
        </x-ui.card>
    @else
        @if ($hasVersion)
            <x-ui.notice role="note">{{ __('admin.value_sets.change_warning') }}</x-ui.notice>
        @endif

        @if ($organization->isIndividual())
            <x-ui.notice>{{ __('admin.value_sets.individual_note') }}</x-ui.notice>
        @endif

        <form wire:submit="save" class="flex flex-col gap-6">
            {{-- The organization's values (08 · 8.1) --}}
            <section class="flex flex-col gap-4" aria-labelledby="values-title">
                <div class="flex flex-wrap items-baseline justify-between gap-3">
                    <h2 id="values-title" class="text-lg font-semibold">{{ __('admin.value_sets.values') }}</h2>
                    <p class="text-sm text-grey-700">{{ __('admin.value_sets.count', ['count' => count($items), 'min' => \App\Models\OrganizationValueSet::MIN_VALUES, 'max' => \App\Models\OrganizationValueSet::MAX_VALUES]) }}</p>
                </div>
                <x-form.error name="items" id="items" />

                @foreach ($items as $index => $item)
                    <x-ui.card as="article" bordered wire:key="item-{{ $item['key'] }}" class="flex flex-col gap-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="flex flex-col gap-1">
                                <h3 class="text-lg font-semibold">{{ $item['name'] }}</h3>
                                @if ($item['value_id'] === null)
                                    <x-ui.badge tone="yellow">{{ __('admin.value_sets.new_badge') }}</x-ui.badge>
                                @endif
                                <x-form.error :name="'items.'.$index.'.name'" :id="'items-'.$index.'-name'" />
                            </div>
                            <div class="-mr-2 flex shrink-0 items-center">
                                <x-ui.icon-button icon="arrow-up" :label="__('admin.value_sets.up', ['name' => $item['name']])" wire:click="moveItem({{ $index }}, -1)" :disabled="$loop->first" />
                                <x-ui.icon-button icon="arrow-down" :label="__('admin.value_sets.down', ['name' => $item['name']])" wire:click="moveItem({{ $index }}, 1)" :disabled="$loop->last" />
                                <x-ui.icon-button icon="close" :label="__('admin.value_sets.remove', ['name' => $item['name']])" wire:click="removeItem({{ $index }})" />
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach (\App\Models\OrganizationValueSetItem::TEXT_FIELDS as $field)
                                <div @class(['flex flex-col gap-1.5', 'md:col-span-2' => $field === 'example'])>
                                    <label for="items-{{ $index }}-{{ $field }}" class="flex items-center gap-2 text-sm font-medium">
                                        @isset($fieldMarkers[$field])<x-ui.badge :tone="$fieldMarkers[$field][1]" aria-hidden="true">{{ $fieldMarkers[$field][0] }}</x-ui.badge>@endisset
                                        {{ __('admin.value_sets.fields.'.$field) }}
                                        @if (in_array($field, $requiredFields, true))
                                            <span class="font-normal text-grey-600">· {{ __('ui.required') }}</span>
                                        @endif
                                    </label>
                                    {{-- Raw: the label carries the scale marker, which x-form.textarea cannot. --}}
                                    <textarea
                                        id="items-{{ $index }}-{{ $field }}"
                                        wire:model="items.{{ $index }}.{{ $field }}"
                                        rows="2"
                                        maxlength="500"
                                        @if (in_array($field, $requiredFields, true)) required aria-required="true" @endif
                                        @if ($errors->has('items.'.$index.'.'.$field)) aria-invalid="true" aria-describedby="items-{{ $index }}-{{ $field }}-error" @endif
                                        class="field w-full"
                                    ></textarea>
                                    <x-form.error :name="'items.'.$index.'.'.$field" :id="'items-'.$index.'-'.$field" />
                                </div>
                            @endforeach
                        </div>
                    </x-ui.card>
                @endforeach

                @unless ($isFull)
                    <x-ui.card class="flex flex-col gap-4 border border-dashed border-grey-350">
                        <x-form.inline>
                            <x-form.select name="pickedValueId" wire:model="pickedValueId" :label="__('admin.value_sets.library')">
                                <option value="">{{ __('admin.value_sets.library_select') }}</option>
                                @foreach ($libraryValues as $libraryValue)
                                    <option value="{{ $libraryValue->id }}">{{ $libraryValue->name }}</option>
                                @endforeach
                            </x-form.select>
                            <x-slot:actions>
                                <x-ui.button tone="outline" size="sm" icon="plus" wire:click="addFromLibrary">{{ __('admin.value_sets.add') }}</x-ui.button>
                            </x-slot:actions>
                        </x-form.inline>
                        <x-form.inline>
                            <x-form.input name="newValueName" wire:model="newValueName" :label="__('admin.value_sets.new_value')" />
                            <x-slot:actions>
                                <x-ui.button tone="outline" size="sm" icon="plus" wire:click="addNew">{{ __('admin.value_sets.add_new') }}</x-ui.button>
                            </x-slot:actions>
                        </x-form.inline>
                        <p class="text-sm text-grey-700">{{ __('admin.value_sets.add_help') }}</p>
                    </x-ui.card>
                @endunless
            </section>

            {{-- The brand base (08 · 8.2) --}}
            <section class="flex flex-col gap-4" aria-labelledby="brand-title">
                <h2 id="brand-title" class="text-lg font-semibold">{{ __('admin.value_sets.brand_title') }}</h2>

                <div class="grid gap-4 md:grid-cols-2">
                    <x-ui.card bordered class="flex flex-col gap-2">
                        <h3 class="flex items-center gap-2 font-semibold"><x-megis.symbol name="root" class="size-6" /> {{ __('admin.value_sets.brand.root') }}</h3>
                        <p class="text-sm text-grey-700">{{ __('admin.value_sets.brand_questions.root') }}</p>
                        <p>{{ collect($items)->pluck('name')->implode(' · ') ?: __('admin.common.none') }}</p>
                    </x-ui.card>

                    @foreach (['purpose' => 'trunk', 'value_proposition' => 'fruit', 'impact' => 'shade'] as $field => $symbol)
                        <x-ui.card bordered class="flex flex-col gap-2">
                            <h3 class="flex items-center gap-2 font-semibold"><x-megis.symbol :name="$symbol" class="size-6" /> {{ __('admin.value_sets.brand.'.$field) }}</h3>
                            <x-form.textarea
                                :name="$field"
                                wire:model="{{ $field }}"
                                :label="__('admin.value_sets.brand_questions.'.$field)"
                                rows="3"
                                maxlength="500"
                                :help="__('admin.value_sets.brand_help')"
                            />
                        </x-ui.card>
                    @endforeach
                </div>
            </section>

            <div>
                <x-ui.button type="submit">{{ __('admin.common.save') }}</x-ui.button>
            </div>
        </form>

        {{-- Versions (08 · 8.4, 8.5) --}}
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
    @endif
</div>
