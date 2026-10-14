{{-- V11·b · Hova tartozik (12 · 12.2). --}}
<form wire:submit="place" class="flex max-w-xl flex-col gap-6">
    <fieldset class="flex flex-col gap-2">
        <legend class="mb-2 text-xl font-semibold">{{ __('processes.occasions.which') }}</legend>
        @foreach ($eligible as $process)
            <label wire:key="eligible-{{ $process->id }}" class="flex items-center gap-3 rounded-card bg-white px-4 py-3">
                <input type="radio" name="choice" value="{{ $process->id }}" wire:model="choice" class="size-4 accent-charcoal">
                <span>
                    <span class="font-semibold">{{ $process->name }}</span>
                    @if ($latest && $latest->process_id === $process->id)
                        <span class="block text-sm text-grey-700">{{ __('processes.occasions.from_calendar', ['date' => $latest->occurred_at->format('m.d.')]) }}</span>
                    @endif
                </span>
            </label>
        @endforeach
        @foreach ($excluded as $process)
            <div class="flex items-center gap-3 rounded-card border border-dashed border-grey-350 px-4 py-3 text-grey-600" aria-disabled="true">
                <span class="size-4 rounded-pill border border-grey-350" aria-hidden="true"></span>
                <span>{{ $process->name }} · {{ __('enums.process_kind.excluded') }}</span>
            </div>
        @endforeach
        <label class="flex items-center gap-3 rounded-card bg-white px-4 py-3">
            <input type="radio" name="choice" value="none" wire:model="choice" class="size-4 accent-charcoal">
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
        <x-ui.button type="submit" tone="coral" size="lg">{{ __('diagnosis.next') }}</x-ui.button>
    </div>
</form>
