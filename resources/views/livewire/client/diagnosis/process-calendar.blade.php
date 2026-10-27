{{-- V6·b · Naptár (09 · 9.1, 9.2). --}}
<div class="flex max-w-2xl flex-col gap-6">
    @if ($failure)
        <x-ui.notice tone="error" :title="$failure">
            <x-slot:action>
                <x-ui.button tone="outline" size="sm" :href="route('client.diagnosis.process-conversation', [$organization, $diagnosis])">{{ __('calendar.talk_instead') }}</x-ui.button>
            </x-slot:action>
        </x-ui.notice>
    @endif

    @if ($connection)
        <x-ui.notice tone="success">
            {{ __('calendar.connected', ['provider' => $connection->providerLabel(), 'date' => $connection->connected_at->format('m.d.')]) }}
            <x-slot:action>
                <x-ui.button tone="outline" size="sm" :href="route('client.diagnosis.process-list', [$organization, $diagnosis])">{{ __('processes.list.title') }}</x-ui.button>
            </x-slot:action>
        </x-ui.notice>
    @endif

    @if (! $choosing)
        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.card as="section" class="flex flex-col gap-2" aria-labelledby="we-read">
                <h3 id="we-read" class="font-semibold">{{ __('calendar.we_read') }}</h3>
                <ul class="flex flex-col gap-2 text-sm">
                    @foreach (['title', 'attendees', 'invitation', 'description'] as $item)
                        <li><span class="font-semibold">{{ __('calendar.read.'.$item) }}</span><br><span class="text-grey-700">{{ __('calendar.read.'.$item.'_help') }}</span></li>
                    @endforeach
                </ul>
            </x-ui.card>
            <x-ui.card as="section" class="flex flex-col gap-2" aria-labelledby="we-never">
                <h3 id="we-never" class="font-semibold">{{ __('calendar.we_never') }}</h3>
                <ul class="flex flex-col gap-2 text-sm">
                    {{-- "soha" on the right of each (prototype V6·b). --}}
                    <li class="flex items-start justify-between gap-3"><span><span class="font-semibold">{{ __('calendar.never.attachments') }}</span><br><span class="text-grey-700">{{ __('calendar.never.attachments_help') }}</span></span><x-ui.badge tone="muted">{{ __('calendar.never.tag') }}</x-ui.badge></li>
                    <li class="flex items-start justify-between gap-3"><span class="font-semibold">{{ __('calendar.never.private') }}</span><x-ui.badge tone="muted">{{ __('calendar.never.tag') }}</x-ui.badge></li>
                </ul>
            </x-ui.card>
        </div>

        <x-ui.card as="section" tone="cream" bordered class="flex flex-col gap-1 text-sm" aria-labelledby="goes-on">
            <h3 id="goes-on" class="text-base font-semibold">{{ __('calendar.goes_on') }}</h3>
            <dl class="flex flex-col gap-1">
                @foreach (['metadata', 'excluded', 'disconnect', 'stays'] as $line)
                    <div class="flex justify-between gap-3">
                        <dt>{{ __('calendar.goes_on_lines.'.$line) }}</dt>
                        <dd class="text-right font-semibold">{{ __('calendar.goes_on_values.'.$line) }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-ui.card>

        <div class="flex flex-wrap items-center gap-3">
            @if ($canConnect)
                <x-ui.button size="lg" icon-right="arrow-right" wire:click="$set('choosing', true)">{{ __('calendar.connect') }}</x-ui.button>
            @endif
            <x-ui.button tone="ghost" :href="route('client.diagnosis.process-conversation', [$organization, $diagnosis])">{{ __('calendar.talk') }}</x-ui.button>
        </div>
    @elseif ($canConnect)
        <div class="flex flex-col gap-3">
            @foreach ($providers as $provider)
                <x-ui.link-row
                    :href="route('client.diagnosis.process-calendar.connect', [$organization, $diagnosis, $provider])"
                    :title="__('calendar.providers.'.$provider)"
                    :meta="__('calendar.provider_help.'.$provider)"
                />
            @endforeach

            <x-ui.card as="form" wire:submit="connectUrl" class="flex flex-col gap-3">
                <h3 class="font-semibold">{{ __('calendar.providers.ics_url') }}</h3>
                <x-form.input name="icsUrl" type="url" wire:model="icsUrl" :label="__('calendar.ics_url')" :help="__('calendar.ics_url_help')" inputmode="url" />
                <div wire:loading wire:target="connectUrl" class="text-sm" role="status">{{ __('calendar.reading') }}</div>
                <x-ui.button type="submit" size="sm" class="self-start">{{ __('calendar.connect_url') }}</x-ui.button>
            </x-ui.card>

            <x-ui.card as="form" wire:submit="connectFile" class="flex flex-col gap-3">
                <h3 class="font-semibold">{{ __('calendar.providers.ics_file') }}</h3>
                <x-form.file name="icsFile" id="ics-file" accept=".ics,text/calendar" wire:model="icsFile" :label="__('calendar.ics_file')" :help="__('calendar.ics_file_help', ['size' => $maxMegabytes])" />
                <div wire:loading wire:target="connectFile,icsFile" class="text-sm" role="status">{{ __('calendar.reading') }}</div>
                <x-ui.button type="submit" size="sm" class="self-start" wire:loading.attr="disabled" wire:target="icsFile">{{ __('calendar.upload') }}</x-ui.button>
            </x-ui.card>

            <x-ui.button tone="ghost" class="self-start" :href="route('client.diagnosis.process-conversation', [$organization, $diagnosis])">{{ __('calendar.talk') }}</x-ui.button>
        </div>
    @endif
</div>
