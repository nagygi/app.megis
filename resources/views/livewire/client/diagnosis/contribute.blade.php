{{-- V11 · Hozzáteszek (12 · 12.1). --}}
<div class="flex max-w-2xl flex-col gap-6" @if ($materials->contains(fn ($material) => $material->status->isWorking())) wire:poll.5s @endif>
    <x-ui.flash />

    @if ($canContribute)
        <section class="flex flex-col gap-3" aria-labelledby="start-measure">
            <h2 id="start-measure" class="font-semibold">{{ __('processes.occasions.measure') }}</h2>
            @if ($canRecordMirror)
                <a href="{{ route('client.mirror.record', ['organization' => $organization, 'diagnozis' => $diagnosis->id]) }}" class="flex items-center justify-between gap-3 rounded-card bg-white px-5 py-4 text-charcoal no-underline hover:bg-paper">
                    <span>
                        <span class="block font-semibold">{{ __('client.menu.mirror') }}</span>
                        <span class="block text-sm text-grey-700">{{ __('processes.occasions.mirror_help') }}</span>
                    </span>
                    <x-megis.icon name="chevron-right" class="size-4 shrink-0" />
                </a>
                <a href="{{ route('client.diagnosis.situation', [$organization, $diagnosis]) }}" class="flex items-center justify-between gap-3 rounded-card bg-white px-5 py-4 text-charcoal no-underline hover:bg-paper">
                    <span>
                        <span class="block font-semibold">{{ __('processes.occasions.situation') }}</span>
                        <span class="block text-sm text-grey-700">{{ __('processes.occasions.situation_help') }}</span>
                    </span>
                    <x-megis.icon name="chevron-right" class="size-4 shrink-0" />
                </a>
            @endif
        </section>

        <form wire:submit="storeUpload" class="flex flex-col gap-3 rounded-card bg-white p-5">
            <h2 class="font-semibold">{{ __('processes.occasions.upload') }}</h2>
            <div class="flex flex-col gap-1.5">
                <label for="occasion-file" class="text-sm font-medium">{{ __('processes.occasions.file') }}</label>
                <input id="occasion-file" type="file" wire:model="file" aria-describedby="occasion-file-help" class="text-base">
                <p id="occasion-file-help" class="text-sm text-grey-600">{{ __('admin.upload.rules', $rules) }}</p>
                <div wire:loading wire:target="file" class="text-sm" role="status">{{ __('admin.upload.uploading') }}</div>
                <x-form.error name="file" id="file" />
            </div>
            <x-ui.button type="submit" tone="dark" size="sm" class="self-start" wire:loading.attr="disabled" wire:target="file,storeUpload">{{ __('processes.occasions.upload_button') }}</x-ui.button>
        </form>

        @if ($this->isLeader())
            <p class="text-sm text-grey-700">
                @if ($connection)
                    {{ __('calendar.connected', ['provider' => $connection->providerLabel(), 'date' => $connection->connected_at->format('m.d.')]) }}
                @else
                    {{ __('processes.occasions.no_calendar') }}
                @endif
                · <a href="{{ route('client.profile', $organization) }}" class="font-semibold">{{ __('client.menu.profile') }}</a>
            </p>
        @endif
    @else
        <p class="rounded-card bg-grey-100 p-4 text-sm">{{ __('processes.occasions.closed') }}</p>
    @endif

    @if ($openMaterials->isNotEmpty())
        <section class="flex flex-col gap-2" aria-labelledby="processing">
            <h2 id="processing" class="font-semibold">{{ __('processes.occasions.processing_title') }} · {{ $openMaterials->count() }}</h2>
            <ul class="flex flex-col gap-2">
                @foreach ($openMaterials as ['material' => $material, 'state' => $state])
                    <li wire:key="material-{{ $material->uuid }}" class="flex flex-wrap items-center justify-between gap-3 rounded-card bg-white px-4 py-3">
                        <span>
                            <span class="block font-semibold">{{ $material->is_situation ? __('processes.occasions.situation') : ($material->original_name ?? $material->source->label()) }}</span>
                            <span class="block text-sm text-grey-700">
                                {{ collect([$material->occasion?->process?->name, ($material->occasion?->occurred_at ?? $material->created_at)->format('m.d.')])->filter()->implode(' · ') }}
                                · <span @class(['font-semibold text-coral-ink' => $state === 'failed'])>{{ __('processes.occasions.states.'.$state) }}</span>
                            </span>
                        </span>
                        @if ($canContribute)
                            <span class="flex flex-wrap gap-2">
                                @if ($state === 'failed')
                                    <x-ui.button size="sm" tone="outline" wire:click="retry('{{ $material->uuid }}')">{{ __('processes.occasions.retry') }}</x-ui.button>
                                    <x-ui.button size="sm" tone="outline" wire:click="remove('{{ $material->uuid }}')" wire:confirm="{{ __('processes.occasions.confirm_remove') }}">{{ __('processes.occasions.remove') }}</x-ui.button>
                                @elseif ($state === 'unplaced')
                                    <x-ui.button size="sm" :href="route('client.diagnosis.place', [$organization, $diagnosis, $material])">{{ __('processes.occasions.place') }}</x-ui.button>
                                @elseif ($state === 'experience_open')
                                    <x-ui.button size="sm" :href="route('client.diagnosis.occasion-experience', [$organization, $diagnosis, $material])">{{ __('processes.occasions.finish') }}</x-ui.button>
                                @endif
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($materials->isNotEmpty())
        <a href="{{ route('client.diagnosis.situations', [$organization, $diagnosis]) }}" class="self-start text-sm font-semibold">{{ __('processes.occasions.situations_link') }}</a>
    @endif
</div>
