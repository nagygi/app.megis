{{-- V3 · A szervezet értékei (08 · 8.1). --}}
<div class="flex max-w-2xl flex-col gap-6" @if ($dictations !== []) wire:poll.2s="pollDictations" @endif>
    <x-ui.flash />

    <p class="text-grey-700">{{ trans_choice('diagnosis.organization_values.count', count($items), ['count' => count($items)]) }}</p>

    @if (! $canEdit)
        <x-ui.notice>{{ $givenBy ? __('diagnosis.organization_values.given_by', ['name' => $givenBy]) : __('diagnosis.organization_values.readonly') }}</x-ui.notice>
    @elseif ($hasVersion)
        <x-ui.notice tone="warning">{{ __('diagnosis.organization_values.version_warning') }}</x-ui.notice>
    @endif

    <x-form.error name="items" id="items" />

    <ul class="flex flex-col gap-3">
        @foreach ($items as $index => $item)
            @php($isOpen = $canEdit && $openKey === $item['key'])
            <x-ui.card as="li" wire:key="org-{{ $item['key'] }}" class="flex flex-col gap-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2 pt-2">
                        <h3 class="text-lg font-semibold">{{ $item['name'] }}</h3>
                        @if ($item['value_id'] !== null && in_array($item['value_id'], $ownValueIds, true))
                            <x-ui.badge tone="coral">{{ __('diagnosis.organization_values.yours_too') }}</x-ui.badge>
                        @endif
                    </div>
                    @if ($canEdit)
                        <div class="-mt-1 -mr-2 flex items-center gap-1">
                            <x-ui.button tone="ghost" size="sm" wire:click="toggle('{{ $item['key'] }}')" aria-expanded="{{ $isOpen ? 'true' : 'false' }}">{{ $isOpen ? __('diagnosis.close') : __('diagnosis.edit') }}</x-ui.button>
                            <x-ui.icon-button
                                icon="close"
                                :label="__('diagnosis.own_values.remove', ['name' => $item['name']])"
                                wire:click="remove('{{ $item['key'] }}')"
                                wire:confirm="{{ __('diagnosis.organization_values.remove_confirm', ['name' => $item['name']]) }}"
                                data-confirm-tone="danger"
                                :data-confirm-label="__('diagnosis.own_values.remove_yes')"
                            />
                        </div>
                    @endif
                </div>

                @if ($isOpen)
                    @foreach (\App\Models\OrganizationValueSetItem::TEXT_FIELDS as $field)
                        <div class="flex flex-col gap-1.5">
                            <x-form.textarea
                                :name="'items.'.$index.'.'.$field"
                                wire:model.live.blur="items.{{ $index }}.{{ $field }}"
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
                        <p class="text-sm font-semibold text-coral-ink">{{ __('diagnosis.organization_values.ends_missing') }}</p>
                    @endif
                    <x-form.error :name="'items.'.$index.'.low_example'" :id="'items-'.$index.'-low'" />
                    <x-form.error :name="'items.'.$index.'.high_example'" :id="'items-'.$index.'-high'" />
                @endif
            </x-ui.card>
        @endforeach
    </ul>

    @if ($canEdit && ! $isFull)
        <x-ui.card class="grid gap-4 sm:grid-cols-2">
            <form wire:submit="addFromLibrary" class="flex flex-col gap-2">
                <x-form.select name="pickedValueId" wire:model="pickedValueId" :label="__('diagnosis.organization_values.library')">
                    <option value="">{{ __('diagnosis.organization_values.library_select') }}</option>
                    @foreach ($library as $value)
                        <option value="{{ $value->id }}">{{ $value->name }}</option>
                    @endforeach
                </x-form.select>
                <x-ui.button type="submit" tone="outline" size="sm" icon="plus" class="self-start">{{ __('diagnosis.organization_values.add') }}</x-ui.button>
            </form>
            <form wire:submit="addNew" class="flex flex-col gap-2">
                <x-form.input name="newValueName" wire:model="newValueName" :label="__('diagnosis.organization_values.new_value')" />
                <x-ui.button type="submit" tone="outline" size="sm" icon="plus" class="self-start">{{ __('diagnosis.organization_values.add') }}</x-ui.button>
            </form>
        </x-ui.card>
    @endif

    <x-diagnosis.next
        wire:click="next"
        :disabled-reason="$canEdit && count($items) < \App\Models\OrganizationValueSet::MIN_VALUES ? __('diagnosis.organization_values.too_few', ['min' => \App\Models\OrganizationValueSet::MIN_VALUES]) : null"
    >{{ $this->nextLabel(\App\Enums\DiagnosisStep::OrganizationValues) }}</x-diagnosis.next>
</div>
