{{-- V11 · Hozzáteszek (12 · 12.1). --}}
<div class="flex max-w-2xl flex-col gap-6" @if ($materials->contains(fn ($material) => $material->status->isWorking())) wire:poll.5s @endif>
    <x-ui.flash />

    @if ($canContribute)
        <section class="flex flex-col gap-3" aria-labelledby="start-measure">
            <h2 id="start-measure" class="text-lg font-semibold">{{ __('processes.occasions.measure') }}</h2>
            @if ($canRecordMirror)
                <x-ui.link-row :href="route('client.mirror.record', ['organization' => $organization, 'diagnozis' => $diagnosis->id])" :title="__('client.menu.mirror')" :meta="__('processes.occasions.mirror_help')" />
                <x-ui.link-row :href="route('client.diagnosis.situation', [$organization, $diagnosis])" :title="__('processes.occasions.situation')" :meta="__('processes.occasions.situation_help')" />
            @endif
        </section>

        <x-ui.card as="form" wire:submit="storeUpload" class="flex flex-col gap-3">
            <h2 class="text-lg font-semibold">{{ __('processes.occasions.upload') }}</h2>
            <x-form.file name="file" id="occasion-file" wire:model="file" :label="__('processes.occasions.file')" :help="__('processes.occasions.upload_rules', $rules)" />
            <div wire:loading wire:target="file" class="text-sm" role="status">{{ __('processes.occasions.uploading') }}</div>
            <x-ui.button type="submit" size="sm" icon="upload" class="self-start" wire:loading.attr="disabled" wire:target="file">{{ __('processes.occasions.upload_button') }}</x-ui.button>
        </x-ui.card>

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
        <x-ui.notice>{{ __('processes.occasions.closed') }}</x-ui.notice>
    @endif

    @if ($openMaterials->isNotEmpty())
        <section class="flex flex-col gap-2" aria-labelledby="processing">
            <h2 id="processing" class="text-lg font-semibold">{{ __('processes.occasions.processing_title') }} · {{ $openMaterials->count() }}</h2>
            <ul class="flex flex-col gap-2">
                @foreach ($openMaterials as ['material' => $material, 'state' => $state])
                    <x-ui.card as="li" padding="sm" wire:key="material-{{ $material->uuid }}" class="flex flex-wrap items-center justify-between gap-3">
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
                                    <x-ui.button size="sm" tone="outline" icon="refresh" wire:click="retry('{{ $material->uuid }}')">{{ __('processes.occasions.retry') }}</x-ui.button>
                                    <x-ui.button size="sm" tone="danger" wire:click="remove('{{ $material->uuid }}')" wire:confirm="{{ __('processes.occasions.confirm_remove') }}" data-confirm-tone="danger">{{ __('processes.occasions.remove') }}</x-ui.button>
                                @elseif ($state === 'unplaced')
                                    <x-ui.button size="sm" icon-right="chevron-right" :href="route('client.diagnosis.place', [$organization, $diagnosis, $material])">{{ __('processes.occasions.place') }}</x-ui.button>
                                @elseif ($state === 'experience_open')
                                    <x-ui.button size="sm" icon-right="chevron-right" :href="route('client.diagnosis.occasion-experience', [$organization, $diagnosis, $material])">{{ __('processes.occasions.finish') }}</x-ui.button>
                                @endif
                            </span>
                        @endif
                    </x-ui.card>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($materials->isNotEmpty())
        <x-ui.link-row :href="route('client.diagnosis.situations', [$organization, $diagnosis])" :title="__('processes.occasions.situations_link')" />
    @endif
</div>
