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
                    <span wire:sort:handle class="mt-2.5 cursor-grab select-none px-1 text-grey-600" title="{{ __('admin.list_editor.drag') }}" aria-hidden="true">⋮⋮</span>

                    <div class="flex flex-1 flex-col gap-1">
                        <input
                            id="{{ $id }}-{{ $index }}"
                            type="text"
                            wire:model="{{ $name }}.{{ $index }}"
                            maxlength="{{ $maxLength }}"
                            aria-label="{{ __('admin.list_editor.item_label', ['label' => $label, 'number' => $index + 1]) }}"
                            @if ($errors->has($name.'.'.$index)) aria-invalid="true" aria-describedby="{{ $id }}-{{ $index }}-error" @endif
                            @class([
                                'w-full rounded-field border bg-white px-3.5 py-2 text-base text-charcoal',
                                'border-coral-dark' => $errors->has($name.'.'.$index),
                                'border-grey-350 focus:border-charcoal' => ! $errors->has($name.'.'.$index),
                            ])
                        >
                        <x-form.error :name="$name.'.'.$index" :id="$id.'-'.$index" />
                    </div>

                    <div class="flex shrink-0 items-center gap-1 pt-1" wire:sort:ignore>
                        <button type="button" wire:click="moveListItem('{{ $name }}', {{ $index }}, -1)" @disabled($loop->first) class="rounded-field px-2 py-1 text-sm hover:bg-grey-100 disabled:opacity-40" aria-label="{{ __('admin.list_editor.up', ['number' => $index + 1]) }}">↑</button>
                        <button type="button" wire:click="moveListItem('{{ $name }}', {{ $index }}, 1)" @disabled($loop->last) class="rounded-field px-2 py-1 text-sm hover:bg-grey-100 disabled:opacity-40" aria-label="{{ __('admin.list_editor.down', ['number' => $index + 1]) }}">↓</button>
                        <button type="button" wire:click="removeListItem('{{ $name }}', {{ $index }})" class="rounded-field px-2 py-1 text-sm hover:bg-grey-100" aria-label="{{ __('admin.list_editor.remove', ['number' => $index + 1]) }}">×</button>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif

    <x-form.error :name="$name" :id="$id" />

    <div>
        <x-ui.button tone="outline" size="sm" wire:click="addListItem('{{ $name }}')">{{ __('admin.list_editor.add') }}</x-ui.button>
    </div>
</fieldset>
