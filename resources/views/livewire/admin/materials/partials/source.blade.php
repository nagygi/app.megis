{{-- The audio and the transcript of a material (06 · 6.5, 6.6). The component provides deleteAudio(). --}}
<x-ui.card as="section" bordered class="flex flex-col gap-3" aria-labelledby="source-{{ $material->id }}">
    <x-ui.section-header :title="__('admin.materials.source')" :id="'source-'.$material->id">
        <x-slot:actions>
            <x-ui.button tone="ghost" size="sm" :href="route('admin.materials.show', $material)">{{ __('admin.materials.open') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.section-header>

    @if ($material->hasAudio())
        <div class="flex flex-wrap items-center gap-3">
            <audio controls preload="none" src="{{ route('admin.materials.audio', $material) }}" class="w-full max-w-md"></audio>
            <x-ui.button
                tone="danger"
                size="sm"
                wire:click="deleteAudio"
                wire:confirm="{{ __('admin.materials.confirm_delete_audio') }}"
                data-confirm-tone="danger"
                :data-confirm-label="__('admin.materials.delete_audio')"
            >{{ __('admin.materials.delete_audio') }}</x-ui.button>
        </div>
    @elseif ($material->audio_deleted_at)
        <p class="text-sm text-grey-700">{{ __('admin.materials.audio_was_deleted', ['date' => $material->audio_deleted_at->format('Y.m.d.')]) }}</p>
    @endif

    @if (($material->segments ?? []) !== [])
        <details class="group">
            <summary class="inline-flex min-h-10 cursor-pointer list-none items-center gap-1 font-semibold [&::-webkit-details-marker]:hidden">
                {{ __('admin.materials.transcript') }}
                <x-megis.icon name="chevron-down" class="transition-transform duration-200 group-open:rotate-180" />
            </summary>
            <div class="mt-3 flex max-h-96 flex-col gap-2 overflow-y-auto text-sm">
                @foreach ($material->segments as $segment)
                    <p @class(['rounded-field px-2 py-1', 'bg-yellow-pale' => $highlightSpeaker !== null && $segment['speaker'] === $highlightSpeaker])>
                        <span class="font-semibold">{{ $segment['speaker'] }}:</span> {{ $segment['text'] }}
                    </p>
                @endforeach
            </div>
        </details>
    @endif
</x-ui.card>
