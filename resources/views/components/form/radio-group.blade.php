{{--
    A choice of one, as tiles: the whole tile is the target, the chosen one
    turns yellow-pale with a charcoal edge (prototype .opt).
    - options: value => label, or value => ['label' => …, 'help' => …];
    - wire:model and other wire: attributes go on every radio, the rest on
      the fieldset;
    - inline: the tiles side by side where they fit.
--}}
@props([
    'name',
    'label',
    'options',
    'help' => null,
    'required' => false,
    'inline' => false,
    'id' => null,
])

@php
    $id ??= str_replace(['.', '[', ']'], ['-', '-', ''], $name);
    $hasError = $errors->has($name);
    $describedBy = collect([$help ? $id.'-help' : null, $hasError ? $id.'-error' : null])->filter()->implode(' ');
    $inputAttributes = $attributes->whereStartsWith(['wire:', 'x-model']);
@endphp

<fieldset @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif {{ $attributes->whereDoesntStartWith(['wire:', 'x-model'])->class(['flex flex-col gap-2']) }}>
    <legend class="mb-1.5 text-sm font-medium text-charcoal">
        {{ $label }}
        @if ($required)
            <span class="font-normal text-grey-600">· {{ __('ui.required') }}</span>
        @endif
    </legend>

    <div @class(['flex gap-2', 'flex-wrap' => $inline, 'flex-col' => ! $inline])>
        @foreach ($options as $value => $option)
            @php($option = is_array($option) ? $option : ['label' => $option])
            <label @class([
                'flex cursor-pointer items-start gap-3 rounded-field border bg-white px-4 py-3 transition-colors duration-200',
                'border-grey-350 hover:border-grey-500 has-checked:border-charcoal has-checked:bg-yellow-pale',
                'has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-coral',
            ])>
                <input
                    type="radio"
                    name="{{ $name }}"
                    value="{{ $value }}"
                    class="mt-0.5 size-5 shrink-0 accent-charcoal focus-visible:outline-none"
                    @if ($required) required @endif
                    {{ $inputAttributes }}
                >
                <span class="flex flex-col gap-0.5">
                    <span class="font-medium">{{ $option['label'] }}</span>
                    @if (! empty($option['help']))
                        <span class="text-sm text-grey-700">{{ $option['help'] }}</span>
                    @endif
                </span>
            </label>
        @endforeach
    </div>

    @if ($help)
        <p id="{{ $id }}-help" class="text-sm text-grey-600">{{ $help }}</p>
    @endif

    <x-form.error :name="$name" :id="$id" />
</fieldset>
