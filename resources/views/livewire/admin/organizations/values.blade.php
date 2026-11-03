@php
    $fieldMarkers = ['low_example' => ['1', 'coral'], 'high_example' => ['6', 'green']];
    $requiredFields = ['low_example', 'high_example'];
@endphp

<div class="flex flex-col gap-6">
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

                {{-- At five values the adding stays in place, greyed out with the reason (08 · 8.1). --}}
                <x-ui.card :bordered="false" class="flex flex-col gap-4 border border-dashed border-grey-350 *:max-w-3xl">
                    <x-form.inline>
                        <x-form.select name="pickedValueId" wire:model="pickedValueId" :label="__('admin.value_sets.library')" :disabled="$isFull">
                            <option value="">{{ __('admin.value_sets.library_select') }}</option>
                            @foreach ($libraryValues as $libraryValue)
                                <option value="{{ $libraryValue->id }}">{{ $libraryValue->name }}</option>
                            @endforeach
                        </x-form.select>
                        <x-slot:actions>
                            <x-ui.button tone="outline" size="sm" icon="plus" wire:click="addFromLibrary" :disabled="$isFull">{{ __('admin.value_sets.add') }}</x-ui.button>
                        </x-slot:actions>
                    </x-form.inline>
                    <x-form.inline>
                        <x-form.input name="newValueName" wire:model="newValueName" :label="__('admin.value_sets.new_value')" :disabled="$isFull" />
                        <x-slot:actions>
                            <x-ui.button tone="outline" size="sm" icon="plus" wire:click="addNew" :disabled="$isFull">{{ __('admin.value_sets.add_new') }}</x-ui.button>
                        </x-slot:actions>
                    </x-form.inline>
                    @if ($isFull)
                        <p class="text-sm text-grey-700">{{ __('admin.value_sets.too_many', ['max' => \App\Models\OrganizationValueSet::MAX_VALUES]) }}</p>
                    @endif
                    <p class="text-sm text-grey-700">{{ __('admin.value_sets.add_help') }}</p>
                </x-ui.card>
            </section>

            <div>
                <x-ui.button type="submit">{{ __('admin.common.save') }}</x-ui.button>
            </div>
        </form>

        @include('livewire.admin.organizations.partials.value-set-versions')
    @endif
</div>
