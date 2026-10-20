@php
    $status = $material->status;
    $meta = collect([
        $material->context->label(),
        $material->source->label(),
        $material->organization?->name,
        $material->created_at->format('Y.m.d. H:i'),
        $material->durationLabel(),
    ])->filter()->implode(' · ');
@endphp

<div class="flex max-w-5xl flex-col gap-6" @if ($status->isWorking()) wire:poll.5s @endif>
    <x-ui.flash />

    <x-admin.page-header :title="$material->original_name ?? $material->context->label()" :description="$meta" :spaced="false">
        <x-slot:badges>
            <x-ui.badge :tone="$status === \App\Enums\MaterialStatus::Failed ? 'coral' : ($status === \App\Enums\MaterialStatus::Processed ? 'green' : 'yellow')">{{ $status->label() }}</x-ui.badge>
        </x-slot:badges>
    </x-admin.page-header>

    @if ($status === \App\Enums\MaterialStatus::Failed)
        <x-ui.notice tone="error" :title="__('admin.materials.failed')">
            @if ($material->error)
                <p class="text-sm break-words">{{ $material->error }}</p>
            @endif
            <x-slot:action>
                <x-ui.button size="sm" wire:click="retry">{{ __('admin.materials.retry') }}</x-ui.button>
            </x-slot:action>
        </x-ui.notice>
    @endif

    @if ($status === \App\Enums\MaterialStatus::AwaitingSpeakers)
        {{-- 06 · 6.3: each speaker to a user of the organization, or skipped --}}
        <x-ui.card as="form" bordered wire:submit="assign" class="flex flex-col gap-4">
            <x-ui.section-header :title="__('admin.materials.speakers')" :description="__('admin.materials.speakers_help')" />
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

            <x-ui.button type="submit" class="self-start">{{ __('admin.materials.start_analysis') }}</x-ui.button>
        </x-ui.card>
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
