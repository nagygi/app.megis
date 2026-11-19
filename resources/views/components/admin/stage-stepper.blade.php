{{--
    The stage line of a main diagnosis (StageGuide::steps, 17 · 17.5): every
    stage with its day, who moves it on, and what it means in a bubble. A
    green tick for a done stage, a coral ring for the current one, a grey
    ring for the coming ones; a closed diagnosis ends where it was closed.
    Scrolls sideways on a phone, like the tabs.
--}}
@props(['steps'])

@php
    $reached = fn (array $step) => in_array($step['state'], ['done', 'current', 'closed'], true);
@endphp

<nav aria-label="{{ __('admin.main_diagnoses.stage_line') }}" {{ $attributes->class(['-mx-1 overflow-x-auto px-1']) }}>
    <ol class="flex">
        @foreach ($steps as $index => $step)
            @php
                $stage = $step['stage']->value;
                $next = $steps[$index + 1] ?? null;
            @endphp
            <li class="flex min-w-28 flex-1 flex-col items-center gap-1 pb-1 text-center" @if ($step['state'] === 'current') aria-current="step" @endif>
                <span class="flex w-full items-center" aria-hidden="true">
                    <span @class(['h-0.5 flex-1', 'invisible' => $loop->first, 'bg-green' => $reached($step), 'bg-grey-250' => ! $reached($step)])></span>
                    @switch ($step['state'])
                        @case ('done')
                            <span class="grid size-7 shrink-0 place-items-center rounded-pill bg-green text-cream"><x-megis.icon name="check" class="size-3.5" /></span>
                            @break
                        @case ('current')
                            <span class="grid size-7 shrink-0 place-items-center rounded-pill border-2 border-coral bg-white"><span class="size-3 rounded-pill bg-coral"></span></span>
                            @break
                        @case ('closed')
                            <span class="grid size-7 shrink-0 place-items-center rounded-pill bg-charcoal text-cream"><x-megis.icon name="close" class="size-3.5" /></span>
                            @break
                        @default
                            <span @class(['size-7 shrink-0 rounded-pill border-2 bg-white', 'border-grey-350' => $step['state'] === 'upcoming', 'border-dashed border-grey-300' => $step['state'] === 'skipped'])></span>
                    @endswitch
                    <span @class(['h-0.5 flex-1', 'invisible' => $loop->last, 'bg-green' => $next && $reached($next), 'bg-grey-250' => ! ($next && $reached($next))])></span>
                </span>

                <span class="flex items-center gap-1">
                    <span @class([
                        'text-sm',
                        'font-semibold text-charcoal' => in_array($step['state'], ['current', 'closed'], true),
                        'font-medium text-charcoal' => $step['state'] === 'done',
                        'font-medium text-grey-700' => in_array($step['state'], ['upcoming', 'skipped'], true),
                    ])>{{ $step['stage']->label() }}</span>
                    <span class="sr-only">· {{ __('admin.main_diagnoses.stage_states.'.$step['state']) }}</span>
                    <x-ui.info :text="__('admin.main_diagnoses.stages_help.'.$stage)" :label="$step['stage']->label().': '.__('ui.info')" />
                </span>

                @if ($step['date'])
                    <span class="text-xs text-grey-700">
                        @if ($step['planned'])
                            {{ __($step['stage'] === \App\Enums\MainDiagnosisStage::Running ? 'admin.main_diagnoses.stage_dates.deadline' : 'admin.main_diagnoses.stage_dates.planned', ['date' => $step['date']->format('Y.m.d.')]) }}
                        @elseif ($step['state'] === 'current')
                            {{ __('admin.main_diagnoses.stage_dates.since', ['date' => $step['date']->format('Y.m.d.')]) }}
                        @else
                            {{ $step['date']->format('Y.m.d.') }}
                        @endif
                    </span>
                @endif

                {{-- Who makes a coming stage happen: the admin or the leader. --}}
                @if ($step['state'] === 'upcoming')
                    <x-ui.badge :tone="$step['actor'] === 'leader' ? 'yellow' : 'muted'">{{ __('admin.main_diagnoses.actors.'.$step['actor']) }}</x-ui.badge>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
