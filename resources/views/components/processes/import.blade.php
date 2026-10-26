{{--
    Several processes at once from a written or spoken text (11 · 11.2, 11.6).
    The Livewire component uses WithProcessImport, and WithDictation for the
    microphone; "parents" are the organization's top-level processes. The rows
    are edited here and added together.
--}}
@props(['rows', 'parents', 'canDictate' => false, 'pending' => false, 'bordered' => false])

<x-ui.card as="section" :padding="$bordered ? 'lg' : 'md'" class="flex flex-col gap-4" aria-labelledby="import-title">
    <div class="flex flex-col gap-1">
        <h3 id="import-title" class="text-lg font-semibold">{{ __('processes.import.title') }}</h3>
        <p class="text-sm text-grey-700" aria-live="polite">
            {{ $rows === [] ? __('processes.import.lead') : trans_choice('processes.import.found', count($rows), ['count' => count($rows)]).' '.__('processes.import.review') }}
        </p>
    </div>

    @if ($rows === [])
        <form wire:submit="parseImport" class="flex flex-col gap-3">
            <x-form.textarea name="importText" wire:model="importText" :label="__('processes.import.text')" rows="5" maxlength="10000" />
            @if ($canDictate)
                <x-diagnosis.dictate field="importText" :label="__('processes.import.text')" :pending="$pending" />
            @endif
            <div wire:loading wire:target="parseImport" class="text-sm" role="status">{{ __('processes.import.parsing') }}</div>
            <x-ui.button type="submit" tone="outline" class="self-start" data-focus-errors>{{ __('processes.import.parse') }}</x-ui.button>
        </form>
    @else
        <form wire:submit="importProcesses" class="flex flex-col gap-4">
            <ol class="flex flex-col gap-3">
                @foreach ($rows as $index => $row)
                    <x-ui.card as="li" tone="paper" padding="sm" wire:key="import-row-{{ $row['key'] }}" class="flex flex-col gap-3">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-semibold">{{ __('processes.import.row', ['number' => $index + 1]) }}</p>
                            <x-ui.icon-button icon="close" class="-mt-2 -mr-2" :label="__('processes.import.remove', ['name' => $row['name']])" wire:click="removeImportRow('{{ $row['key'] }}')" />
                        </div>
                        <x-form.input name="importRows.{{ $index }}.name" wire:model="importRows.{{ $index }}.name" :label="__('processes.list.fields.name')" required />
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-form.select name="importRows.{{ $index }}.rhythm" wire:model="importRows.{{ $index }}.rhythm" :label="__('processes.list.fields.rhythm')">
                                <option value="">–</option>
                                @foreach (\App\Enums\ProcessRhythm::cases() as $rhythmOption)
                                    <option value="{{ $rhythmOption->value }}">{{ $rhythmOption->label() }}</option>
                                @endforeach
                            </x-form.select>
                            <x-form.select name="importRows.{{ $index }}.parent" wire:model="importRows.{{ $index }}.parent" :label="__('processes.list.fields.parent')">
                                <option value="">{{ __('processes.list.parent_none') }}</option>
                                @if (count($rows) > 1)
                                    <optgroup label="{{ __('processes.import.from_text') }}">
                                        @foreach ($rows as $other)
                                            @continue($other['key'] === $row['key'])
                                            <option value="new:{{ $other['key'] }}">{{ $other['name'] }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                                @if ($parents->isNotEmpty())
                                    <optgroup label="{{ __('processes.import.existing') }}">
                                        @foreach ($parents as $parent)
                                            <option value="process:{{ $parent->id }}">{{ $parent->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </x-form.select>
                        </div>
                        <x-form.textarea name="importRows.{{ $index }}.description" wire:model="importRows.{{ $index }}.description" :label="__('processes.sheet.description')" rows="2" maxlength="2000" />
                    </x-ui.card>
                @endforeach
            </ol>
            <div class="flex flex-wrap gap-2">
                <x-ui.button type="submit" icon="plus" data-focus-errors>{{ trans_choice('processes.import.save', count($rows), ['count' => count($rows)]) }}</x-ui.button>
                <x-ui.button tone="outline" wire:click="backToImportText">{{ __('processes.import.back') }}</x-ui.button>
                <x-ui.button tone="ghost" wire:click="cancelImport">{{ __('processes.import.cancel') }}</x-ui.button>
            </div>
        </form>
    @endif
</x-ui.card>
