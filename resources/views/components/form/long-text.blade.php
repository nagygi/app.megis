{{--
    A longer text the user writes, tells or uploads: Leírom · Elmondom ·
    Feltöltöm. The transcript or the file's text goes to the end of the field
    and the field shows again, editable; what does not fit "max", the AI
    condenses. The Livewire component uses WithDictation and WithFileUploads,
    "name" is the property and the dictation field at once, and "pending" is
    true while the transcript is on its way. Without "tools" (read only, or no
    right to dictate) it is a plain field. A "caption" slot gives the label
    its own markup; "label" still names the field to screen readers.
--}}
@props([
    'name',
    'label',
    'help' => null,
    'required' => false,
    'id' => null,
    'rows' => 4,
    'max' => null,
    'pending' => false,
    'upload' => true,
    'tools' => true,
    'labelHidden' => false,
])

@php
    $id ??= str_replace(['.', '[', ']'], ['-', '-', ''], $name);
    $hasError = $errors->has($name);
    $describedBy = collect([$help ? $id.'-help' : null, $hasError ? $id.'-error' : null])->filter()->implode(' ');
    $panel = 'flex min-h-28 flex-col justify-center gap-2 rounded-field border-[1.5px] border-dashed border-grey-300 bg-paper p-4';
@endphp

<div class="flex flex-col gap-2" x-data="longText({ field: @js($name), failed: @js(__('ui.long_text.failed')) })" x-on:dictation-applied.window="applied($event)">
    @isset($caption)
        <label for="{{ $id }}" {{ $caption->attributes->class(['sr-only' => $labelHidden]) }}>{{ $caption }}</label>
    @else
        <x-form.label :for="$id" :required="$required" :class="$labelHidden ? 'sr-only' : ''">{{ $label }}</x-form.label>
    @endisset

    @if ($tools)
        <x-ui.segmented :label="__('ui.long_text.ways', ['label' => $label])">
            <x-ui.segment active="mode === 'write'" selected x-on:click="mode = 'write'">{{ __('ui.long_text.write') }}</x-ui.segment>
            <x-ui.segment active="mode === 'speak'" x-on:click="mode = 'speak'" x-show="canRecord">{{ __('ui.long_text.speak') }}</x-ui.segment>
            @if ($upload)
                <x-ui.segment active="mode === 'upload'" x-on:click="mode = 'upload'">{{ __('ui.long_text.upload') }}</x-ui.segment>
            @endif
        </x-ui.segmented>
    @endif

    <div x-show="mode === 'write'" class="flex flex-col gap-1.5">
        <textarea
            x-ref="field"
            x-on:input="length = $el.value.length"
            id="{{ $id }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            @if ($max) maxlength="{{ $max }}" @endif
            @if ($required) required aria-required="true" @endif
            @if ($hasError) aria-invalid="true" @endif
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->class(['field w-full']) }}
        >{{ $slot }}</textarea>

        @if ($help || $max)
            <div class="flex items-start justify-between gap-3 text-sm text-grey-600">
                @if ($help)
                    <p id="{{ $id }}-help">{{ $help }}</p>
                @endif
                @if ($max)
                    {{-- Read out only when asked: a live count would talk at every keystroke. --}}
                    <p class="ml-auto shrink-0 tabular-nums" x-cloak><span x-text="length"></span> / {{ $max }}</p>
                @endif
            </div>
        @endif
    </div>

    @if ($tools)
        <div x-show="mode === 'speak'" x-cloak class="{{ $panel }}">
            <x-diagnosis.dictate :field="$name" :label="$label" :pending="$pending" :start="__('ui.long_text.record_start')" :stop="__('ui.long_text.record_stop')" />
            <p class="text-sm text-grey-600">{{ __('ui.long_text.speak_hint', ['minutes' => intdiv((int) config('megis.materials.dictation_max_seconds'), 60)]) }}</p>
        </div>

        @if ($upload)
            <div x-show="mode === 'upload'" x-cloak class="{{ $panel }}">
                <label for="{{ $id }}-file" class="sr-only">{{ __('ui.long_text.file_label', ['label' => $label]) }}</label>
                <input
                    id="{{ $id }}-file"
                    type="file"
                    accept=".{{ implode(',.', config('megis.materials.text_extensions')) }}"
                    x-on:change="read($event)"
                    x-bind:disabled="reading"
                    aria-describedby="{{ $id }}-file-help"
                    class="w-full text-sm text-grey-700 file:mr-3 file:min-h-10 file:cursor-pointer file:rounded-pill file:border-[1.5px] file:border-charcoal file:bg-transparent file:px-4 file:text-sm file:font-semibold file:text-charcoal hover:file:bg-yellow-pale disabled:cursor-wait"
                >
                <p id="{{ $id }}-file-help" class="text-sm text-grey-600">
                    {{ __('ui.long_text.upload_hint', ['formats' => implode(', ', config('megis.materials.text_extensions')), 'size' => intdiv((int) config('megis.materials.text_max_kilobytes'), 1024)]) }}
                </p>
                <p x-show="reading" x-cloak class="text-sm" role="status">{{ __('ui.long_text.reading') }}</p>
                <p x-show="error" x-text="error" x-cloak class="text-sm text-coral-ink" role="alert"></p>
            </div>
        @endif
    @endif

    <x-ui.notice tone="warning" x-show="notice" x-cloak role="status" class="text-sm"><span x-text="notice"></span></x-ui.notice>
    <x-form.error :name="$name" :id="$id" />
</div>
