{{-- V6 · Folyamatok (11 · 11.1): two ways to the same list. --}}
<div class="flex max-w-2xl flex-col gap-6">
    <p class="text-xl font-semibold">{{ __('processes.entry.question') }}</p>

    @if ($main->acceptsInput())
        <div class="flex flex-col gap-3">
            <h2 class="font-semibold">{{ __('processes.entry.choose') }}</h2>
            <a href="{{ route('client.diagnosis.process-calendar', [$main->diagnosis->organization, $main->diagnosis]) }}" class="flex items-center justify-between gap-3 rounded-card bg-yellow-pale px-5 py-4 text-charcoal no-underline hover:bg-yellow-light">
                <span>
                    <span class="block font-semibold">{{ __('processes.entry.calendar') }} <x-ui.badge tone="dark">{{ __('processes.entry.recommended') }}</x-ui.badge></span>
                    <span class="block text-sm text-grey-700">{{ __('processes.entry.calendar_help') }}</span>
                </span>
                <x-megis.icon name="chevron-right" class="size-4 shrink-0" />
            </a>
            <a href="{{ route('client.diagnosis.process-conversation', [$main->diagnosis->organization, $main->diagnosis]) }}" class="flex items-center justify-between gap-3 rounded-card bg-white px-5 py-4 text-charcoal no-underline hover:bg-paper">
                <span class="flex items-center gap-3">
                    <x-megis.symbol name="spiral" class="size-8 text-coral-dark" />
                    <span>
                        <span class="block font-semibold">{{ __('processes.entry.conversation') }}</span>
                        <span class="block text-sm text-grey-700">{{ __('processes.entry.conversation_help') }}</span>
                    </span>
                </span>
                <x-megis.icon name="chevron-right" class="size-4 shrink-0" />
            </a>
        </div>
    @endif

    <section class="flex flex-col gap-2 rounded-card border border-grey-250 p-5" aria-labelledby="measured-title">
        <h2 id="measured-title" class="font-semibold">{{ __('processes.entry.measured_title') }}</h2>
        <p><span class="font-semibold">{{ __('processes.entry.measured_identity') }}</span> · <span class="text-sm text-grey-700">{{ __('processes.entry.measured_identity_help') }}</span></p>
        <p><span class="font-semibold">{{ __('processes.entry.measured_own') }}</span> · <span class="text-sm text-grey-700">{{ __('processes.entry.measured_own_help') }}</span></p>
    </section>

    <details class="rounded-card bg-white p-5">
        <summary class="cursor-pointer font-semibold">{{ __('processes.entry.sample_title') }}</summary>
        <div class="mt-3 flex flex-col gap-1 text-sm">
            <p class="text-base font-semibold">{{ __('processes.entry.sample_name') }}</p>
            <p>{{ __('processes.entry.sample_line') }}</p>
            <p>{{ __('processes.entry.sample_responsible') }}</p>
            <p>{{ __('processes.entry.sample_participants') }}</p>
            <p>{{ __('processes.entry.sample_metric') }}</p>
        </div>
    </details>

    <div class="flex flex-wrap items-center gap-3">
        @if ($hasProcesses)
            <x-ui.button tone="dark" :href="route('client.diagnosis.process-list', [$main->diagnosis->organization, $main->diagnosis])">{{ __('processes.entry.to_list') }}</x-ui.button>
        @elseif ($main->acceptsInput())
            <x-ui.button tone="coral" size="lg" :href="route('client.diagnosis.process-calendar', [$main->diagnosis->organization, $main->diagnosis])">{{ __('processes.entry.start_calendar') }}</x-ui.button>
        @endif
        @if ($main->acceptsInput())
            <x-ui.button tone="outline" wire:click="skip">{{ __('processes.entry.skip') }}</x-ui.button>
        @endif
    </div>
</div>
