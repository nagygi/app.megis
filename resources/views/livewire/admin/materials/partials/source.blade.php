{{-- The audio and the transcript of a material (06 · 6.5, 6.6). The component provides deleteAudio(). --}}
<section class="flex flex-col gap-3 rounded-card border border-grey-250 bg-white p-5" aria-labelledby="source-{{ $material->id }}">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 id="source-{{ $material->id }}" class="text-lg font-semibold">{{ __('admin.materials.source') }}</h2>
        <a href="{{ route('admin.materials.show', $material) }}" class="text-sm font-semibold text-charcoal">{{ __('admin.materials.open') }}</a>
    </div>

    @if ($material->hasAudio())
        <div class="flex flex-wrap items-center gap-3">
            <audio controls preload="none" src="{{ route('admin.materials.audio', $material) }}" class="w-full max-w-md"></audio>
            <x-ui.button tone="outline" size="sm" wire:click="deleteAudio" wire:confirm="{{ __('admin.materials.confirm_delete_audio') }}">{{ __('admin.materials.delete_audio') }}</x-ui.button>
        </div>
    @elseif ($material->audio_deleted_at)
        <p class="text-sm text-grey-700">{{ __('admin.materials.audio_was_deleted', ['date' => $material->audio_deleted_at->format('Y.m.d.')]) }}</p>
    @endif

    @if (($material->segments ?? []) !== [])
        <details>
            <summary class="cursor-pointer font-semibold">{{ __('admin.materials.transcript') }}</summary>
            <div class="mt-3 flex max-h-96 flex-col gap-2 overflow-y-auto text-sm">
                @foreach ($material->segments as $segment)
                    <p @class(['rounded-field px-2 py-1', 'bg-yellow-pale' => $highlightSpeaker !== null && $segment['speaker'] === $highlightSpeaker])>
                        <span class="font-semibold">{{ $segment['speaker'] }}:</span> {{ $segment['text'] }}
                    </p>
                @endforeach
            </div>
        </details>
    @endif
</section>
