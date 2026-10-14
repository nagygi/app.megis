{{-- V6·b · Naptár (09 · 9.1, 9.2). --}}
<div class="flex max-w-2xl flex-col gap-6">
    <p class="text-xl font-semibold">{{ __('calendar.lead') }}</p>

    @if ($failure)
        <div class="flex flex-col gap-2 rounded-card bg-coral-light p-4 text-black" role="alert">
            <p class="font-semibold">{{ $failure }}</p>
            <a href="{{ route('client.diagnosis.process-conversation', [$organization, $diagnosis]) }}" class="font-semibold">{{ __('calendar.talk_instead') }}</a>
        </div>
    @endif

    @if ($connection)
        <p class="rounded-card bg-green-soft p-4 text-sm">
            {{ __('calendar.connected', ['provider' => $connection->providerLabel(), 'date' => $connection->connected_at->format('m.d.')]) }}
            · <a href="{{ route('client.diagnosis.process-list', [$organization, $diagnosis]) }}" class="font-semibold">{{ __('processes.list.title') }}</a>
        </p>
    @endif

    @if (! $choosing)
        <div class="grid gap-4 sm:grid-cols-2">
            <section class="flex flex-col gap-2 rounded-card bg-white p-5" aria-labelledby="we-read">
                <h2 id="we-read" class="font-semibold">{{ __('calendar.we_read') }}</h2>
                <ul class="flex flex-col gap-2 text-sm">
                    @foreach (['title', 'attendees', 'invitation', 'description'] as $item)
                        <li><span class="font-semibold">{{ __('calendar.read.'.$item) }}</span><br><span class="text-grey-700">{{ __('calendar.read.'.$item.'_help') }}</span></li>
                    @endforeach
                </ul>
            </section>
            <section class="flex flex-col gap-2 rounded-card bg-white p-5" aria-labelledby="we-never">
                <h2 id="we-never" class="font-semibold">{{ __('calendar.we_never') }}</h2>
                <ul class="flex flex-col gap-2 text-sm">
                    <li><span class="font-semibold">{{ __('calendar.never.attachments') }}</span><br><span class="text-grey-700">{{ __('calendar.never.attachments_help') }}</span></li>
                    <li><span class="font-semibold">{{ __('calendar.never.private') }}</span></li>
                </ul>
            </section>
        </div>

        <section class="flex flex-col gap-1 rounded-card border border-grey-250 p-5 text-sm" aria-labelledby="goes-on">
            <h2 id="goes-on" class="font-semibold">{{ __('calendar.goes_on') }}</h2>
            @foreach (['metadata', 'excluded', 'disconnect', 'stays'] as $line)
                <p>{{ __('calendar.goes_on_lines.'.$line) }}</p>
            @endforeach
        </section>

        <div class="flex flex-wrap items-center gap-4">
            @if ($canConnect)
                <x-ui.button tone="coral" size="lg" wire:click="$set('choosing', true)">{{ __('calendar.connect') }}</x-ui.button>
            @endif
            <a href="{{ route('client.diagnosis.process-conversation', [$organization, $diagnosis]) }}" class="font-semibold">{{ __('calendar.talk') }}</a>
        </div>
    @elseif ($canConnect)
        <div class="flex flex-col gap-3">
            @foreach ($providers as $provider)
                <a href="{{ route('client.diagnosis.process-calendar.connect', [$organization, $diagnosis, $provider]) }}" class="flex items-center justify-between gap-3 rounded-card bg-white px-5 py-4 text-charcoal no-underline hover:bg-paper">
                    <span>
                        <span class="block font-semibold">{{ __('calendar.providers.'.$provider) }}</span>
                        <span class="block text-sm text-grey-700">{{ __('calendar.provider_help.'.$provider) }}</span>
                    </span>
                    <x-megis.icon name="chevron-right" class="size-4 shrink-0" />
                </a>
            @endforeach

            <form wire:submit="connectUrl" class="flex flex-col gap-3 rounded-card bg-white p-5">
                <h2 class="font-semibold">{{ __('calendar.providers.ics_url') }}</h2>
                <x-form.input name="icsUrl" type="url" wire:model="icsUrl" :label="__('calendar.ics_url')" :help="__('calendar.ics_url_help')" inputmode="url" />
                <div wire:loading wire:target="connectUrl" class="text-sm" role="status">{{ __('calendar.reading') }}</div>
                <x-ui.button type="submit" tone="dark" size="sm" class="self-start" wire:loading.attr="disabled">{{ __('calendar.connect_url') }}</x-ui.button>
            </form>

            <form wire:submit="connectFile" class="flex flex-col gap-3 rounded-card bg-white p-5">
                <h2 class="font-semibold">{{ __('calendar.providers.ics_file') }}</h2>
                <div class="flex flex-col gap-1.5">
                    <label for="ics-file" class="text-sm font-medium">{{ __('calendar.ics_file') }}</label>
                    <input id="ics-file" type="file" accept=".ics,text/calendar" wire:model="icsFile" aria-describedby="ics-file-help" class="text-base">
                    <p id="ics-file-help" class="text-sm text-grey-600">{{ __('calendar.ics_file_help', ['size' => $maxMegabytes]) }}</p>
                    <x-form.error name="icsFile" id="ics-file" />
                </div>
                <div wire:loading wire:target="connectFile,icsFile" class="text-sm" role="status">{{ __('calendar.reading') }}</div>
                <x-ui.button type="submit" tone="dark" size="sm" class="self-start" wire:loading.attr="disabled">{{ __('calendar.upload') }}</x-ui.button>
            </form>

            <a href="{{ route('client.diagnosis.process-conversation', [$organization, $diagnosis]) }}" class="font-semibold">{{ __('calendar.talk') }}</a>
        </div>
    @endif
</div>
