{{--
    List editor (NFR · 3): one input per item, ordered by drag and drop or with
    the up and down buttons; the first item matters most. Works with the
    EditsLists trait of the Livewire component. "name" is the array property.
--}}
@props([
    'name',
    'label',
    'items' => [],
    'help' => null,
    'required' => false,
    'maxLength' => 255,
    'marker' => null,
    'markerTone' => 'neutral',
])

@php
    $id = 'list-'.str_replace('_', '-', $name);
    $hasError = $errors->has($name);
@endphp

<fieldset class="flex flex-col gap-2" @if ($help || $hasError) aria-describedby="{{ collect([$help ? $id.'-help' : null, $hasError ? $id.'-error' : null])->filter()->implode(' ') }}" @endif>
    <legend class="mb-1 flex items-center gap-2 text-sm font-medium">
        @if ($marker !== null)
            <x-ui.badge :tone="$markerTone" aria-hidden="true">{{ $marker }}</x-ui.badge>
        @endif
        {{ $label }}
        @if ($required)
            <span class="font-normal text-grey-600">· {{ __('ui.required') }}</span>
        @endif
    </legend>

    @if ($help)
        <p id="{{ $id }}-help" class="text-sm text-grey-600">{{ $help }}</p>
    @endif

    @if (count($items) > 0)
        <ol wire:sort="sortListItem('{{ $name }}', $item, $position)" class="flex flex-col gap-2">
            @foreach ($items as $index => $item)
                <li wire:key="{{ $name }}-{{ $index }}-{{ md5((string) $item) }}" wire:sort:item="{{ $index }}" class="flex items-start gap-2">
                    <span wire:sort:handle class="grid size-11 shrink-0 cursor-grab place-items-center text-grey-600" title="{{ __('admin.list_editor.drag') }}" aria-hidden="true">
                        <x-megis.icon name="drag" />
                    </span>

                    <div class="flex flex-1 flex-col gap-1">
                        <input
                            id="{{ $id }}-{{ $index }}"
                            type="text"
                            wire:model="{{ $name }}.{{ $index }}"
                            maxlength="{{ $maxLength }}"
                            aria-label="{{ __('admin.list_editor.item_label', ['label' => $label, 'number' => $index + 1]) }}"
                            @if ($errors->has($name.'.'.$index)) aria-invalid="true" aria-describedby="{{ $id }}-{{ $index }}-error" @endif
                            class="field w-full"
                        >
                        <x-form.error :name="$name.'.'.$index" :id="$id.'-'.$index" />
                    </div>

                    <div class="flex shrink-0 items-center" wire:sort:ignore>
                        <x-ui.icon-button icon="arrow-up" :label="__('admin.list_editor.up', ['number' => $index + 1])" wire:click="moveListItem('{{ $name }}', {{ $index }}, -1)" :disabled="$loop->first" />
                        <x-ui.icon-button icon="arrow-down" :label="__('admin.list_editor.down', ['number' => $index + 1])" wire:click="moveListItem('{{ $name }}', {{ $index }}, 1)" :disabled="$loop->last" />
                        <x-ui.icon-button icon="close" :label="__('admin.list_editor.remove', ['number' => $index + 1])" wire:click="removeListItem('{{ $name }}', {{ $index }})" />
                    </div>
                </li>
            @endforeach
        </ol>
    @endif

    <x-form.error :name="$name" :id="$id" />

    <div>
        <x-ui.button tone="outline" size="sm" icon="plus" wire:click="addListItem('{{ $name }}')">{{ __('admin.list_editor.add') }}</x-ui.button>
    </div>
</fieldset>
