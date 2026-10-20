@php($status = $material->status)

<div class="flex max-w-4xl flex-col gap-6" @if ($status->isWorking()) wire:poll.5s @endif>
    <x-ui.flash />

    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-semibold">{{ $material->original_name ?? $material->context->label() }}</h1>
        <p class="text-grey-700">
            {{ $material->context->label() }} · {{ $material->source->label() }}
            @if ($material->organization) · {{ $material->organization->name }}@endif
            · {{ $material->created_at->format('Y.m.d. H:i') }}
            @if ($material->durationLabel()) · {{ $material->durationLabel() }}@endif
        </p>
        <div><x-ui.badge :tone="$status === \App\Enums\MaterialStatus::Failed ? 'coral' : ($status === \App\Enums\MaterialStatus::Processed ? 'green' : 'yellow')">{{ $status->label() }}</x-ui.badge></div>
    </div>

    @if ($status === \App\Enums\MaterialStatus::Failed)
        <div class="flex flex-col items-start gap-3 rounded-card border border-grey-250 bg-white p-5" role="alert">
            <p class="font-semibold">{{ __('admin.materials.failed') }}</p>
            @if ($material->error)
                <p class="text-sm text-grey-700 break-words">{{ $material->error }}</p>
            @endif
            <x-ui.button size="sm" wire:click="retry">{{ __('admin.materials.retry') }}</x-ui.button>
        </div>
    @endif

    @if ($status === \App\Enums\MaterialStatus::AwaitingSpeakers)
        {{-- 06 · 6.3: each speaker to a user of the organization, or skipped --}}
        <form wire:submit="assign" class="flex flex-col gap-4 rounded-card border border-grey-250 bg-white p-5">
            <h2 class="text-lg font-semibold">{{ __('admin.materials.speakers') }}</h2>
            <p class="text-sm text-grey-700">{{ __('admin.materials.speakers_help') }}</p>
            <x-form.error name="assignments" id="assignments" />

            @foreach ($speakers as $index => $speaker)
                <div wire:key="speaker-{{ $index }}" class="grid gap-3 rounded-field bg-paper p-4 md:grid-cols-[1fr_16rem]">
                    <div class="flex flex-col gap-1">
                        <p class="font-semibold">{{ $speaker['label'] }}</p>
                        <ul class="flex flex-col gap-1 text-sm text-grey-700">
                            @foreach ($speaker['samples'] as $sample)
                                <li>„{{ $sample }}”</li>
                            @endforeach
                        </ul>
                    </div>
                    <x-form.select :name="'assignments.'.$index" wire:model="assignments.{{ $index }}" :label="__('admin.materials.user')">
                        <option value="">{{ __('admin.materials.skip') }}</option>
                        @foreach ($members as $member)
                            <option value="{{ $member->user_id }}">{{ $member->user->name }}</option>
                        @endforeach
                    </x-form.select>
                </div>
            @endforeach

            <div><x-ui.button type="submit">{{ __('admin.materials.start_analysis') }}</x-ui.button></div>
        </form>
    @endif

    @if ($diagnoses->isNotEmpty())
        <section class="flex flex-col gap-2" aria-labelledby="created-diagnoses">
            <h2 id="created-diagnoses" class="text-lg font-semibold">{{ __('admin.materials.diagnoses') }}</h2>
            <ul class="flex flex-col gap-1">
                @foreach ($diagnoses as $diagnosis)
                    <li><a href="{{ route('admin.diagnoses.show', $diagnosis) }}" class="font-semibold text-charcoal">{{ $diagnosis->name }}</a> · {{ $diagnosis->user?->name }}@if ($diagnosis->speaker) · {{ $diagnosis->speaker }}@endif</li>
                @endforeach
            </ul>
        </section>
    @endif

    @include('livewire.admin.materials.partials.source', ['material' => $material, 'highlightSpeaker' => null])
</div>
