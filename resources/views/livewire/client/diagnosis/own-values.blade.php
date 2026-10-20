{{-- V2 · where from, V2·b · the list, V2·c · adding a value (24); the peer's T3. --}}
<div class="flex max-w-2xl flex-col gap-6">
    <x-ui.flash />

    @if ($screen === 'from')
        {{-- V2 --}}
        <p class="text-xl font-semibold">{{ __('diagnosis.own_values.question') }}</p>

        <div class="flex flex-col gap-3">
            @if ($mirror && $editable)
                <button type="button" wire:click="importMirror" class="flex items-center justify-between gap-3 rounded-card bg-white px-5 py-4 text-left hover:bg-paper">
                    <span>
                        <span class="block font-semibold">{{ __('diagnosis.own_values.from_mirror', ['date' => $mirror->created_at->format('m.d.')]) }}</span>
                        <span class="block text-sm text-grey-700">{{ __('diagnosis.own_values.from_mirror_help', ['count' => $mirrorCount]) }}</span>
                    </span>
                    <x-megis.icon name="chevron-right" class="size-4 shrink-0" />
                </button>
            @endif

            @if ($canRecord && $editable)
                <a href="{{ $recordUrl }}" class="flex items-center justify-between gap-3 rounded-card bg-white px-5 py-4 text-charcoal no-underline hover:bg-paper">
                    <span>
                        <span class="block font-semibold">{{ __('diagnosis.own_values.tell') }}</span>
                        <span class="block text-sm text-grey-700">{{ __('diagnosis.own_values.tell_help') }}</span>
                    </span>
                    <x-megis.icon name="chevron-right" class="size-4 shrink-0" />
                </a>
            @endif

            @if ($editable)
                <button type="button" wire:click="show('add')" class="flex items-center justify-between gap-3 rounded-card bg-white px-5 py-4 text-left hover:bg-paper">
                    <span>
                        <span class="block font-semibold">{{ __('diagnosis.own_values.add_or_pick') }}</span>
                        <span class="block text-sm text-grey-700">{{ __('diagnosis.own_values.add_or_pick_help') }}</span>
                    </span>
                    <x-megis.icon name="chevron-right" class="size-4 shrink-0" />
                </button>
            @endif
        </div>

        @if ($items !== [])
            <button type="button" wire:click="show('list')" class="self-start font-semibold underline">{{ __('diagnosis.own_values.back_to_list') }}</button>
        @endif
    @elseif ($screen === 'list')
        {{-- V2·b --}}
        @if ($newMirror && $editable)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-card bg-yellow-pale px-4 py-3">
                <span>{{ __('diagnosis.own_values.new_mirror', ['date' => $newMirror->created_at->format('m.d.')]) }}</span>
                <button type="button" wire:click="importMirror" class="font-semibold underline">{{ __('diagnosis.own_values.review_new_mirror') }}</button>
            </div>
        @endif

        <div class="flex flex-col gap-1">
            <p class="text-xl font-semibold">{{ __('diagnosis.own_values.list_lead') }}</p>
            <p class="text-grey-700">{{ trans_choice('diagnosis.own_values.list_count', count($items), ['count' => count($items)]) }} · {{ __('diagnosis.own_values.suggested', ['count' => config('diagnosis.own_values.suggested')]) }}</p>
        </div>

        <x-form.error name="items" id="items" />

        <ul class="flex flex-col gap-3">
            @foreach ($items as $index => $item)
                @php
                    $state = $states[$item['key']] ?? 'none';
                    $count = $occurrences['counts'][$item['value_id'] ?? 0] ?? 0;
                @endphp
                <li wire:key="own-{{ $item['key'] }}" class="flex flex-col gap-3 rounded-card bg-white p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-semibold">{{ $item['name'] }}</h2>
                            <x-ui.badge :tone="['strong' => 'green', 'partial' => 'yellow', 'none' => 'muted'][$state]">{{ __('diagnosis.own_values.state.'.$state) }}</x-ui.badge>
                        </div>
                        @if ($editable)
                            <button type="button" wire:click="remove('{{ $item['key'] }}')" class="rounded-pill p-1 hover:bg-grey-100">
                                <x-megis.icon name="close" class="size-4" :label="__('diagnosis.own_values.remove', ['name' => $item['name']])" />
                            </button>
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
                            <button type="button" wire:click="edit('{{ $item['key'] }}')" class="font-semibold underline">{{ __('diagnosis.edit') }}</button>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>

        @if ($editable)
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button tone="outline" wire:click="show('add')" :disabled="count($items) >= $max">{{ __('diagnosis.own_values.new_value') }}</x-ui.button>
                <button type="button" wire:click="show('from')" class="font-semibold underline">{{ __('diagnosis.own_values.where_from') }}</button>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <x-ui.button tone="coral" size="lg" wire:click="next">{{ __('diagnosis.next') }}</x-ui.button>
                <x-diagnosis.saved />
            </div>
        @else
            <p class="rounded-card bg-grey-100 p-4 text-sm">{{ __('diagnosis.own_values.locked') }}</p>
        @endif
    @else
        {{-- V2·c --}}
        <p class="text-xl font-semibold">{{ $editingKey ? __('diagnosis.own_values.edit_title') : __('diagnosis.own_values.add_title') }}</p>

        @if (! $editingKey)
            <div class="flex flex-col gap-3 rounded-card bg-white p-5">
                @if ($canRecord)
                    <a href="{{ $recordUrl }}" class="font-semibold">{{ __('diagnosis.own_values.tell') }} · <span class="font-normal text-grey-700">{{ __('diagnosis.own_values.tell_help') }}</span></a>
                @endif

                @if (($draft['value_id'] ?? null) === null)
                    <div class="flex flex-col gap-2">
                        <label for="library-search" class="font-semibold">{{ __('diagnosis.own_values.pick_library') }}</label>
                        <input id="library-search" type="search" wire:model.live.debounce.300ms="librarySearch" class="rounded-field border border-grey-350 bg-white px-3.5 py-2 text-base focus:border-charcoal" placeholder="{{ __('diagnosis.own_values.search_placeholder') }}">
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
                @endif
            </div>
        @endif

        <form wire:submit="addDraft" class="flex flex-col gap-5 rounded-card bg-white p-5">
            @if (($draft['value_id'] ?? null) !== null)
                <div class="flex flex-col gap-1">
                    <span class="text-sm font-medium">{{ __('diagnosis.own_values.fields.name') }}</span>
                    <span class="text-lg font-semibold">{{ $draft['name'] }}</span>
                    @unless ($editingKey)
                        <button type="button" wire:click="clearPick" class="self-start text-sm font-semibold underline">{{ __('diagnosis.own_values.pick_other') }}</button>
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
                <x-ui.button type="submit" tone="dark">{{ $editingKey ? __('diagnosis.save') : __('diagnosis.own_values.add') }}</x-ui.button>
                <button type="button" wire:click="show('{{ $items === [] ? 'from' : 'list' }}')" class="font-semibold underline">{{ __('diagnosis.cancel') }}</button>
            </div>
        </form>
    @endif
</div>
