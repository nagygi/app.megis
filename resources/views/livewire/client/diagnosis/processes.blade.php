{{-- V6 · Folyamatok (11 · 11.1): two ways to the same list. --}}
<div class="flex max-w-2xl flex-col gap-6">
    <h2 class="text-xl font-semibold">{{ __('processes.entry.question') }}</h2>

    @if ($main->acceptsInput())
        <div class="flex flex-col gap-3">
            <h3 class="font-semibold">{{ __('processes.entry.choose') }}</h3>
            <x-ui.link-row tone="pale" :href="route('client.diagnosis.process-calendar', [$main->diagnosis->organization, $main->diagnosis])" :meta="__('processes.entry.calendar_help')">
                {{ __('processes.entry.calendar') }} <x-ui.badge tone="dark">{{ __('processes.entry.recommended') }}</x-ui.badge>
            </x-ui.link-row>
            <x-ui.link-row
                symbol="spiral"
                :href="route('client.diagnosis.process-conversation', [$main->diagnosis->organization, $main->diagnosis])"
                :title="__('processes.entry.conversation')"
                :meta="__('processes.entry.conversation_help')"
            />
        </div>
    @endif

    <x-ui.card as="section" tone="cream" bordered class="flex flex-col gap-2" aria-labelledby="measured-title">
        <h3 id="measured-title" class="font-semibold">{{ __('processes.entry.measured_title') }}</h3>
        <p><span class="font-semibold">{{ __('processes.entry.measured_identity') }}</span> · <span class="text-sm text-grey-700">{{ __('processes.entry.measured_identity_help') }}</span></p>
        <p><span class="font-semibold">{{ __('processes.entry.measured_own') }}</span> · <span class="text-sm text-grey-700">{{ __('processes.entry.measured_own_help') }}</span></p>
    </x-ui.card>

    <x-ui.disclosure :summary="__('processes.entry.sample_title')">
        <div class="flex flex-col gap-1 text-sm">
            <p class="text-base font-semibold">{{ __('processes.entry.sample_name') }}</p>
            <p>{{ __('processes.entry.sample_line') }}</p>
            <p>{{ __('processes.entry.sample_responsible') }}</p>
            <p>{{ __('processes.entry.sample_participants') }}</p>
            <p>{{ __('processes.entry.sample_metric') }}</p>
        </div>
    </x-ui.disclosure>

    <div class="flex flex-wrap items-center gap-3">
        @if ($hasProcesses)
            <x-ui.button size="lg" icon-right="arrow-right" :href="route('client.diagnosis.process-list', [$main->diagnosis->organization, $main->diagnosis])">{{ __('processes.entry.to_list') }}</x-ui.button>
        @elseif ($main->acceptsInput())
            <x-ui.button size="lg" icon-right="arrow-right" :href="route('client.diagnosis.process-calendar', [$main->diagnosis->organization, $main->diagnosis])">{{ __('processes.entry.start_calendar') }}</x-ui.button>
        @endif
        @if ($main->acceptsInput())
            <x-ui.button tone="outline" wire:click="skip">{{ __('processes.entry.skip') }}</x-ui.button>
        @endif
    </div>
</div>
