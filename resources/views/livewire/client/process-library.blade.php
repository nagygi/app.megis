{{-- Tárak → Folyamatok (11 · 11.5). --}}
<div class="flex max-w-2xl flex-col gap-6">
    <x-ui.flash />

    <p class="text-grey-700">
        {{ collect([
            $connection ? __('processes.library.calendar_connected', ['date' => $connection->connected_at?->format('m.d.')]) : null,
            __('processes.library.routines', ['count' => $processes->count()]),
            __('processes.library.measured_count', ['count' => $measuredCount]),
        ])->filter()->implode(' · ') }}
    </p>

    @if ($processes->isEmpty())
        <x-ui.empty-state symbol="spiral">{{ __('processes.library.empty') }}</x-ui.empty-state>
    @else
        <ul class="flex flex-col gap-2">
            @foreach ($processes as $process)
                @php($isMeasured = $process->kind === \App\Enums\ProcessKind::Routine && $process->isAccepted())
                <li wire:key="library-process-{{ $process->id }}">
                    <x-ui.link-row :href="route('client.processes.show', [$organization, $process])" :title="$process->name">
                        <x-slot:meta>
                            <span @class(['text-coral-ink' => ! $process->isInCalendar()])>
                                {{ collect([
                                    $process->rhythm?->label() ?? $process->rhythm_label,
                                    $process->responsible?->name,
                                    $process->headcount ? __('processes.headcount', ['count' => $process->headcount]) : ($process->participants_count ? __('processes.headcount', ['count' => $process->participants_count]) : null),
                                    $process->isInCalendar() ? __('processes.library.in_calendar') : __('processes.library.not_in_calendar'),
                                    $isMeasured ? __('processes.library.measured') : __('processes.library.not_measured'),
                                ])->filter()->implode(' · ') }}
                            </span>
                        </x-slot:meta>
                    </x-ui.link-row>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($adding)
        <x-ui.card as="form" wire:submit="add" class="flex flex-col gap-4">
            <x-form.input name="name" wire:model="name" :label="__('processes.list.fields.name')" required />
            <x-form.select name="rhythm" wire:model="rhythm" :label="__('processes.list.fields.rhythm')">
                <option value="">–</option>
                @foreach (\App\Enums\ProcessRhythm::cases() as $rhythmOption)
                    <option value="{{ $rhythmOption->value }}">{{ $rhythmOption->label() }}</option>
                @endforeach
            </x-form.select>
            <x-form.textarea name="description" wire:model="description" :label="__('processes.sheet.description')" rows="2" maxlength="2000" />
            <div class="flex flex-wrap gap-2">
                <x-ui.button type="submit" icon="plus" data-focus-errors>{{ __('processes.list.add') }}</x-ui.button>
                <x-ui.button tone="outline" wire:click="$set('adding', false)">{{ __('diagnosis.cancel') }}</x-ui.button>
            </div>
        </x-ui.card>
    @else
        <div>
            <x-ui.button tone="outline" icon="plus" wire:click="$set('adding', true)">{{ __('processes.library.add') }}</x-ui.button>
        </div>
    @endif
</div>
