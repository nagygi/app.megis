{{-- V11·b · Hova tartozik (12 · 12.2): the processes as choice tiles, like x-form.radio-group. --}}
@php
    $tile = 'flex cursor-pointer items-start gap-3 rounded-field border border-grey-350 bg-white px-4 py-3 transition-colors duration-200 hover:border-grey-500 has-checked:border-charcoal has-checked:bg-yellow-pale has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-coral';
    $radio = 'mt-0.5 size-5 shrink-0 accent-charcoal focus-visible:outline-none';
@endphp

<form wire:submit="place" class="flex max-w-xl flex-col gap-6">
    <fieldset class="flex flex-col gap-2">
        {{-- The question is the header's subtitle. --}}
        <legend class="sr-only">{{ __('processes.occasions.which') }}</legend>
        @foreach ($eligible as $process)
            <label wire:key="eligible-{{ $process->id }}" class="{{ $tile }}">
                <input type="radio" name="choice" value="{{ $process->id }}" wire:model="choice" class="{{ $radio }}">
                <span>
                    <span class="font-semibold">{{ $process->name }}</span>
                    {{-- "havi · Gábor S. · 12 fő" (prototype V11·b) --}}
                    <span class="block text-sm text-grey-700">{{ collect([
                        $process->rhythm_label ?? $process->rhythm?->label(),
                        $process->responsible?->name,
                        $process->headcount ? __('processes.headcount', ['count' => $process->headcount]) : null,
                        $latest && $latest->process_id === $process->id ? __('processes.occasions.from_calendar', ['date' => $latest->occurred_at->format('m.d.')]) : null,
                    ])->filter()->implode(' · ') }}</span>
                </span>
            </label>
        @endforeach
        <label class="{{ $tile }}">
            <input type="radio" name="choice" value="none" wire:model="choice" class="{{ $radio }}">
            <span>
                <span class="font-semibold">{{ __('processes.occasions.none') }}</span>
                <span class="block text-sm text-grey-700">{{ __('processes.occasions.none_help') }}</span>
            </span>
        </label>
        {{-- What is not measured comes last (prototype V11·b). --}}
        @foreach ($excluded as $process)
            <div class="flex items-center gap-3 rounded-field border border-dashed border-grey-350 px-4 py-3 text-grey-600" aria-disabled="true">
                <span class="size-5 shrink-0 rounded-full border border-grey-350" aria-hidden="true"></span>
                <span>{{ $process->name }} · {{ __('enums.process_kind.excluded') }}</span>
            </div>
        @endforeach
        <x-form.error name="choice" id="choice" />
    </fieldset>

    <x-form.input name="date" type="date" wire:model="date" :label="__('processes.occasions.when')" :max="today()->toDateString()" required />

    @unless ($material->is_situation)
        <x-form.checkbox name="consent" wire:model="consent" :label="__('processes.occasions.consent')" :help="__('processes.occasions.consent_help')" />
    @endunless

    <div class="flex flex-wrap items-center gap-3">
        <x-ui.button type="submit" size="lg" icon-right="arrow-right" data-focus-errors>{{ __('diagnosis.next') }}</x-ui.button>
        <x-ui.button tone="ghost" :href="route('client.diagnosis.contribute', [$organization, $diagnosis])">{{ __('diagnosis.cancel') }}</x-ui.button>
    </div>
</form>
