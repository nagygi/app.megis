{{-- T5·b · A helyzeteid (12 · 12.5). --}}
<div class="flex max-w-2xl flex-col gap-4">
    <x-ui.flash />

    @if ($materials->isEmpty())
        <x-ui.empty-state symbol="spiral">{{ __('processes.occasions.situations_empty') }}</x-ui.empty-state>
    @else
        <ul class="flex flex-col gap-2">
            @foreach ($materials as $material)
                @php
                    $experience = $material->process_occasion_id ? $experiences->get($material->process_occasion_id) : null;
                    $date = $material->occasion?->occurred_at ?? $material->placed_at;
                @endphp
                <x-ui.card as="li" padding="sm" wire:key="situation-{{ $material->uuid }}" class="flex flex-wrap items-center justify-between gap-3">
                    <span>
                        <span class="block font-semibold">{{ $material->occasion?->process?->name ?? __('processes.occasions.states.unclassified') }}</span>
                        <span class="block text-sm text-grey-700">
                            {{ $date->format('Y.m.d.') }}
                            · {{ $material->is_situation ? ($material->audio_seconds !== null ? __('processes.occasions.spoken', ['duration' => $material->durationLabel()]) : __('processes.occasions.written')) : __('processes.occasions.uploaded') }}
                            @if ($material->process_occasion_id !== null)
                                ·
                                @if ($experience?->isSubmitted())
                                    {{ __('processes.occasions.experience_given') }}
                                @elseif ($editable)
                                    <a href="{{ route('client.diagnosis.occasion-experience', [$organization, $diagnosis, $material]) }}" class="font-semibold text-coral-ink">{{ __('processes.occasions.experience_missing') }}</a>
                                @else
                                    <span class="text-coral-ink">{{ __('processes.occasions.experience_missing') }}</span>
                                @endif
                            @endif
                        </span>
                    </span>
                    @if ($material->audio_path !== null && ! $isImpersonating)
                        <x-ui.button
                            tone="danger"
                            size="sm"
                            wire:click="deleteAudio('{{ $material->uuid }}')"
                            wire:confirm="{{ __('client.recordings.confirm_delete') }}"
                            data-confirm-tone="danger"
                            aria-label="{{ __('client.recordings.delete_label', ['date' => $date->format('Y.m.d.')]) }}"
                        >{{ __('client.recordings.delete') }}</x-ui.button>
                    @endif
                </x-ui.card>
            @endforeach
        </ul>
    @endif
</div>
