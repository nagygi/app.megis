{{-- V11·b · Hova tartozik (12 · 12.2): the processes as choice tiles, like x-form.radio-group. --}}
@php
    $tile = 'flex cursor-pointer items-start gap-3 rounded-field border border-grey-350 bg-white px-4 py-3 transition-colors duration-200 hover:border-grey-500 has-checked:border-charcoal has-checked:bg-yellow-pale has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-coral';
    $radio = 'mt-0.5 size-5 shrink-0 accent-charcoal focus-visible:outline-none';
@endphp

<form wire:submit="place" class="flex max-w-xl flex-col gap-6">
    <fieldset class="flex flex-col gap-2">
        <legend class="mb-2 text-xl font-semibold">{{ __('processes.occasions.which') }}</legend>
        @foreach ($eligible as $process)
            <label wire:key="eligible-{{ $process->id }}" class="{{ $tile }}">
                <input type="radio" name="choice" value="{{ $process->id }}" wire:model="choice" class="{{ $radio }}">
                <span>
                    <span class="font-semibold">{{ $process->name }}</span>
                    @if ($latest && $latest->process_id === $process->id)
                        <span class="block text-sm text-grey-700">{{ __('processes.occasions.from_calendar', ['date' => $latest->occurred_at->format('m.d.')]) }}</span>
                    @endif
                </span>
            </label>
        @endforeach
        @foreach ($excluded as $process)
            <div class="flex items-center gap-3 rounded-field border border-dashed border-grey-350 px-4 py-3 text-grey-600" aria-disabled="true">
                <span class="size-5 shrink-0 rounded-full border border-grey-350" aria-hidden="true"></span>
                <span>{{ $process->name }} · {{ __('enums.process_kind.excluded') }}</span>
            </div>
        @endforeach
        <label class="{{ $tile }}">
            <input type="radio" name="choice" value="none" wire:model="choice" class="{{ $radio }}">
            <span>
                <span class="font-semibold">{{ __('processes.occasions.none') }}</span>
                <span class="block text-sm text-grey-700">{{ __('processes.occasions.none_help') }}</span>
            </span>
        </label>
        <x-form.error name="choice" id="choice" />
    </fieldset>

    <x-form.input name="date" type="date" wire:model="date" :label="__('processes.occasions.when')" :max="today()->toDateString()" required />

    @unless ($material->is_situation)
        <x-form.checkbox name="consent" wire:model="consent" :label="__('processes.occasions.consent')" :help="__('processes.occasions.consent_help')" />
    @endunless

    <div>
        <x-ui.button type="submit" size="lg" icon-right="arrow-right" data-focus-errors>{{ __('diagnosis.next') }}</x-ui.button>
    </div>
</form>
