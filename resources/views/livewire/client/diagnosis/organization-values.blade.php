{{-- V3 · A szervezet értékei (08 · 8.1). --}}
<div class="flex max-w-2xl flex-col gap-6" @if ($dictations !== []) wire:poll.2s="pollDictations" @endif>
    <x-ui.flash />

    <div class="flex flex-col gap-1">
        <p class="text-xl font-semibold">{{ __('diagnosis.organization_values.question') }}</p>
        <p class="text-grey-700">{{ trans_choice('diagnosis.organization_values.count', count($items), ['count' => count($items)]) }}</p>
    </div>

    @if (! $canEdit)
        <p class="rounded-card bg-grey-100 p-4 text-sm">
            {{ $givenBy ? __('diagnosis.organization_values.given_by', ['name' => $givenBy]) : __('diagnosis.organization_values.readonly') }}
        </p>
    @elseif ($hasVersion)
        <p class="rounded-card bg-yellow-pale p-4 text-sm">{{ __('diagnosis.organization_values.version_warning') }}</p>
    @endif

    <x-form.error name="items" id="items" />

    <ul class="flex flex-col gap-3">
        @foreach ($items as $index => $item)
            @php($isOpen = $canEdit && $openKey === $item['key'])
            <li wire:key="org-{{ $item['key'] }}" class="flex flex-col gap-3 rounded-card bg-white p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-semibold">{{ $item['name'] }}</h2>
                        @if ($item['value_id'] !== null && in_array($item['value_id'], $ownValueIds, true))
                            <x-ui.badge tone="coral">{{ __('diagnosis.organization_values.yours_too') }}</x-ui.badge>
                        @endif
                    </div>
                    @if ($canEdit)
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="toggle('{{ $item['key'] }}')" class="text-sm font-semibold underline" aria-expanded="{{ $isOpen ? 'true' : 'false' }}">{{ $isOpen ? __('diagnosis.close') : __('diagnosis.edit') }}</button>
                            <button type="button" wire:click="remove('{{ $item['key'] }}')" class="rounded-pill p-1 hover:bg-grey-100">
                                <x-megis.icon name="close" class="size-4" :label="__('diagnosis.own_values.remove', ['name' => $item['name']])" />
                            </button>
                        </div>
                    @endif
                </div>

                @if ($isOpen)
                    @foreach (\App\Models\OrganizationValueSetItem::TEXT_FIELDS as $field)
                        <div class="flex flex-col gap-1.5">
                            <x-form.textarea
                                :name="'items.'.$index.'.'.$field"
                                wire:model.blur="items.{{ $index }}.{{ $field }}"
                                :label="__('diagnosis.organization_values.fields.'.$field)"
                                :required="in_array($field, ['low_example', 'high_example'], true)"
                                rows="2"
                            />
                            <x-diagnosis.dictate :field="'items.'.$index.'.'.$field" :label="__('diagnosis.organization_values.fields.'.$field)" :pending="isset($dictations['items.'.$index.'.'.$field])" />
                        </div>
                    @endforeach
                @else
                    @if (filled($item['meaning']))
                        <div>
                            <p class="text-sm font-semibold text-grey-700">{{ __('diagnosis.organization_values.meaning_here') }}</p>
                            <p>{{ $item['meaning'] }}</p>
                        </div>
                    @endif
                    @if (filled($item['example']))
                        <div>
                            <p class="text-sm font-semibold text-grey-700">{{ __('diagnosis.own_values.for_example') }}</p>
                            <p>{{ $item['example'] }}</p>
                        </div>
                    @endif
                    @if (blank($item['low_example']) || blank($item['high_example']))
                        <p class="text-sm font-semibold text-coral-dark">{{ __('diagnosis.organization_values.ends_missing') }}</p>
                    @endif
                    <x-form.error :name="'items.'.$index.'.low_example'" :id="'items-'.$index.'-low'" />
                    <x-form.error :name="'items.'.$index.'.high_example'" :id="'items-'.$index.'-high'" />
                @endif
            </li>
        @endforeach
    </ul>

    @if ($canEdit && ! $isFull)
        <div class="grid gap-4 rounded-card bg-white p-5 sm:grid-cols-2">
            <form wire:submit="addFromLibrary" class="flex flex-col gap-2">
                <x-form.select name="pickedValueId" wire:model="pickedValueId" :label="__('diagnosis.organization_values.library')">
                    <option value="">{{ __('diagnosis.organization_values.library_select') }}</option>
                    @foreach ($library as $value)
                        <option value="{{ $value->id }}">{{ $value->name }}</option>
                    @endforeach
                </x-form.select>
                <x-ui.button type="submit" tone="outline" size="sm" class="self-start">{{ __('diagnosis.organization_values.add') }}</x-ui.button>
            </form>
            <form wire:submit="addNew" class="flex flex-col gap-2">
                <x-form.input name="newValueName" wire:model="newValueName" :label="__('diagnosis.organization_values.new_value')" />
                <x-ui.button type="submit" tone="outline" size="sm" class="self-start">{{ __('diagnosis.organization_values.add') }}</x-ui.button>
            </form>
        </div>
    @endif

    <div class="flex flex-wrap items-center gap-4">
        <x-ui.button tone="coral" size="lg" wire:click="next" :disabled="$canEdit && count($items) < \App\Models\OrganizationValueSet::MIN_VALUES">{{ __('diagnosis.next') }}</x-ui.button>
        <x-diagnosis.saved />
    </div>
</div>
