{{-- V2 · where from, V2·b · the list, V2·c · adding a value (24); the peer's T3. --}}
<div class="flex max-w-2xl flex-col gap-6">
    <x-ui.flash />

    @if ($screen === 'from')
        {{-- V2 --}}
        <h2 class="text-xl font-semibold">{{ __('diagnosis.own_values.question') }}</h2>

        <div class="flex flex-col gap-3">
            @if ($mirror && $editable)
                <x-ui.link-row
                    wire:click="importMirror"
                    :title="__('diagnosis.own_values.from_mirror', ['date' => $mirror->created_at->format('m.d.')])"
                    :meta="__('diagnosis.own_values.from_mirror_help', ['count' => $mirrorCount])"
                />
            @endif

            @if ($canRecord && $editable)
                <x-ui.link-row :href="$recordUrl" :title="__('diagnosis.own_values.tell')" :meta="__('diagnosis.own_values.tell_help')" />
            @endif

            @if ($editable)
                <x-ui.link-row wire:click="show('add')" :title="__('diagnosis.own_values.add_or_pick')" :meta="__('diagnosis.own_values.add_or_pick_help')" />
            @endif
        </div>

        @if ($items !== [])
            <x-ui.button tone="ghost" icon="chevron-left" class="self-start" wire:click="show('list')">{{ __('diagnosis.own_values.back_to_list') }}</x-ui.button>
        @endif
    @elseif ($screen === 'list')
        {{-- V2·b --}}
        @if ($newMirror && $editable)
            <x-ui.notice>
                {{ __('diagnosis.own_values.new_mirror', ['date' => $newMirror->created_at->format('m.d.')]) }}
                <x-slot:action>
                    <x-ui.button tone="outline" size="sm" wire:click="importMirror">{{ __('diagnosis.own_values.review_new_mirror') }}</x-ui.button>
                </x-slot:action>
            </x-ui.notice>
        @endif

        <div class="flex flex-col gap-1">
            <h2 class="text-xl font-semibold">{{ __('diagnosis.own_values.list_lead') }}</h2>
            <p class="text-grey-700">{{ trans_choice('diagnosis.own_values.list_count', count($items), ['count' => count($items)]) }} · {{ __('diagnosis.own_values.suggested', ['count' => config('diagnosis.own_values.suggested')]) }}</p>
        </div>

        <x-form.error name="items" id="items" />

        <ul class="flex flex-col gap-3">
            @foreach ($items as $index => $item)
                @php
                    $state = $states[$item['key']] ?? 'none';
                    $count = $occurrences['counts'][$item['value_id'] ?? 0] ?? 0;
                @endphp
                <x-ui.card as="li" wire:key="own-{{ $item['key'] }}" class="flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2 pt-2">
                            <h3 class="text-lg font-semibold">{{ $item['name'] }}</h3>
                            <x-ui.badge :tone="['strong' => 'green', 'partial' => 'yellow', 'none' => 'muted'][$state]">{{ __('diagnosis.own_values.state.'.$state) }}</x-ui.badge>
                        </div>
                        @if ($editable)
                            <x-ui.icon-button
                                icon="close"
                                class="-mt-1 -mr-2"
                                :label="__('diagnosis.own_values.remove', ['name' => $item['name']])"
                                wire:click="remove('{{ $item['key'] }}')"
                                wire:confirm="{{ __('diagnosis.own_values.remove_confirm', ['name' => $item['name']]) }}"
                                data-confirm-tone="danger"
                                :data-confirm-label="__('diagnosis.own_values.remove_yes')"
                            />
                        @endif
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-grey-700">{{ __('diagnosis.own_values.meaning_for_you') }}</p>
                        @if (filled($item['meaning']))
                            <p>{{ $item['meaning'] }}</p>
                        @else
                            <p class="font-semibold text-coral-ink">{{ __('diagnosis.own_values.meaning_missing') }}</p>
                        @endif
                        <x-form.error :name="'items.'.$index.'.meaning'" :id="'items-'.$index.'-meaning'" />
                        <x-form.error :name="'items.'.$index.'.low_example'" :id="'items-'.$index.'-low'" />
                        <x-form.error :name="'items.'.$index.'.high_example'" :id="'items-'.$index.'-high'" />
                    </div>

                    @if (filled($item['example']))
                        <div>
                            <p class="text-sm font-semibold text-grey-700">{{ __('diagnosis.own_values.for_example') }}</p>
                            <p>{{ $item['source'] === 'mirror' ? '„'.$item['example'].'”' : $item['example'] }}</p>
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center justify-between gap-2 text-sm text-grey-700">
                        <span>
                            @if ($item['source'] === 'mirror')
                                {{ __('diagnosis.own_values.source_mirror', ['total' => $occurrences['total'], 'count' => $count]) }}
                            @elseif ($count > 0)
                                {{ __('diagnosis.own_values.source_added_seen', ['total' => $occurrences['total'], 'count' => $count]) }}
                            @else
                                {{ __('diagnosis.own_values.source_added') }}
                            @endif
                        </span>
                        @if ($editable)
                            <x-ui.button tone="ghost" size="sm" wire:click="edit('{{ $item['key'] }}')">{{ __('diagnosis.edit') }}</x-ui.button>
                        @endif
                    </div>
                </x-ui.card>
            @endforeach
        </ul>

        @if ($editable)
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button tone="outline" icon="plus" wire:click="show('add')" :disabled="count($items) >= $max">{{ __('diagnosis.own_values.new_value') }}</x-ui.button>
                <x-ui.button tone="ghost" wire:click="show('from')">{{ __('diagnosis.own_values.where_from') }}</x-ui.button>
            </div>

            <x-diagnosis.next wire:click="next" />
        @else
            <x-ui.notice>{{ __('diagnosis.own_values.locked') }}</x-ui.notice>
        @endif
    @else
        {{-- V2·c --}}
        <h2 class="text-xl font-semibold">{{ $editingKey ? __('diagnosis.own_values.edit_title') : __('diagnosis.own_values.add_title') }}</h2>

        @if (! $editingKey && $canRecord)
            <x-ui.link-row :href="$recordUrl" :title="__('diagnosis.own_values.tell')" :meta="__('diagnosis.own_values.tell_help')" />
        @endif

        @if (! $editingKey && ($draft['value_id'] ?? null) === null)
            <x-ui.card>
                <div class="flex flex-col gap-2">
                    <label for="library-search" class="font-semibold">{{ __('diagnosis.own_values.pick_library') }}</label>
                    <div class="relative">
                        <x-megis.icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-grey-600" />
                        <input id="library-search" type="search" wire:model.live.debounce.300ms="librarySearch" class="field w-full pl-10" placeholder="{{ __('diagnosis.own_values.search_placeholder') }}">
                    </div>
                    @if ($library->isNotEmpty())
                        <ul class="flex max-h-64 flex-col gap-1 overflow-y-auto">
                            @foreach ($library as $value)
                                <li wire:key="library-{{ $value->id }}">
                                    <button type="button" wire:click="pickFromLibrary({{ $value->id }})" class="w-full rounded-field px-3 py-2 text-left hover:bg-grey-100">
                                        <span class="block font-semibold">{{ $value->name }}</span>
                                        @if ($value->meanings !== [])
                                            <span class="block text-sm text-grey-700">{{ $value->meanings[0] }}</span>
                                        @endif
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    </div>
            </x-ui.card>
        @endif

        <x-ui.card as="form" wire:submit="addDraft" class="flex flex-col gap-5">
            @if (($draft['value_id'] ?? null) !== null)
                <div class="flex flex-col gap-1">
                    <span class="text-sm font-medium">{{ __('diagnosis.own_values.fields.name') }}</span>
                    <span class="text-lg font-semibold">{{ $draft['name'] }}</span>
                    @unless ($editingKey)
                        <x-ui.button tone="ghost" size="sm" class="self-start" wire:click="clearPick">{{ __('diagnosis.own_values.pick_other') }}</x-ui.button>
                    @endunless
                </div>
            @else
                <x-form.input name="draft.name" wire:model="draft.name" :label="__('diagnosis.own_values.fields.name')" required />
            @endif

            <x-form.textarea name="draft.meaning" wire:model="draft.meaning" :label="__('diagnosis.own_values.fields.meaning')" required rows="3" />
            <x-form.textarea name="draft.non_meaning" wire:model="draft.non_meaning" :label="__('diagnosis.own_values.fields.non_meaning')" rows="2" />
            <x-form.textarea name="draft.low_example" wire:model="draft.low_example" :label="__('diagnosis.own_values.fields.low_example')" :required="$isIndividual" rows="2" />
            <x-form.textarea name="draft.high_example" wire:model="draft.high_example" :label="__('diagnosis.own_values.fields.high_example')" :required="$isIndividual" rows="2" />

            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button type="submit" data-focus-errors>{{ $editingKey ? __('diagnosis.save') : __('diagnosis.own_values.add') }}</x-ui.button>
                <x-ui.button tone="outline" wire:click="show('{{ $items === [] ? 'from' : 'list' }}')">{{ __('diagnosis.cancel') }}</x-ui.button>
            </div>
        </x-ui.card>
    @endif
</div>
