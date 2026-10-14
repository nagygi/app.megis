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

    <ul class="flex flex-col gap-2">
        @forelse ($processes as $process)
            @php($isMeasured = $process->kind === \App\Enums\ProcessKind::Routine && $process->isAccepted())
            <li wire:key="library-process-{{ $process->id }}">
                <a href="{{ route('client.processes.show', [$organization, $process]) }}" class="flex items-center justify-between gap-3 rounded-card bg-white px-4 py-3 text-charcoal no-underline hover:bg-paper">
                    <span>
                        <span class="block font-semibold">{{ $process->name }}</span>
                        <span @class(['block text-sm', 'text-grey-700' => $process->isInCalendar(), 'text-coral-dark' => ! $process->isInCalendar()])>
                            {{ collect([
                                $process->rhythm?->label() ?? $process->rhythm_label,
                                $process->responsible?->name,
                                $process->headcount ? __('processes.headcount', ['count' => $process->headcount]) : ($process->participants_count ? __('processes.headcount', ['count' => $process->participants_count]) : null),
                                $process->isInCalendar() ? __('processes.library.in_calendar') : __('processes.library.not_in_calendar'),
                                $isMeasured ? __('processes.library.measured') : __('processes.library.not_measured'),
                            ])->filter()->implode(' · ') }}
                        </span>
                    </span>
                    <x-megis.icon name="chevron-right" class="size-4 shrink-0" />
                </a>
            </li>
        @empty
            <li class="rounded-card bg-white px-4 py-3 text-grey-600">{{ __('processes.library.empty') }}</li>
        @endforelse
    </ul>

    @if ($adding)
        <form wire:submit="add" class="flex flex-col gap-4 rounded-card bg-white p-5">
            <x-form.input name="name" wire:model="name" :label="__('processes.list.fields.name')" required />
            <x-form.select name="rhythm" wire:model="rhythm" :label="__('processes.list.fields.rhythm')">
                <option value="">–</option>
                @foreach (\App\Enums\ProcessRhythm::cases() as $rhythmOption)
                    <option value="{{ $rhythmOption->value }}">{{ $rhythmOption->label() }}</option>
                @endforeach
            </x-form.select>
            <x-ui.button type="submit" size="sm" class="self-start">{{ __('processes.list.add') }}</x-ui.button>
        </form>
    @else
        <div>
            <x-ui.button tone="outline" wire:click="$set('adding', true)">{{ __('processes.library.add') }}</x-ui.button>
        </div>
    @endif
</div>
