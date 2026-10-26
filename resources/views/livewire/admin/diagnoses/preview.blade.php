{{-- Előnézet (04 · 4.6): the user's report of a standalone diagnosis, clickable, before publishing. --}}
<div class="flex max-w-7xl flex-col gap-6">
    <x-ui.flash />

    <div class="flex flex-col gap-2">
        <x-admin.page-header :title="__('admin.diagnoses.preview.title')" :back="route('admin.diagnoses.show', $diagnosis)" :back-label="__('admin.diagnoses.preview.back')" :spaced="false">
            <x-slot:badges>
                <x-admin.diagnosis-status :status="$diagnosis->status" />
            </x-slot:badges>
            @if ($canPublish)
                <x-slot:actions>
                    <x-ui.button tone="dark" size="sm" wire:click="publish">{{ __('admin.diagnoses.to_published') }}</x-ui.button>
                </x-slot:actions>
            @endif
        </x-admin.page-header>
        <p class="text-grey-700">{{ $diagnosis->name }} · {{ $diagnosis->organization->name }} · {{ $diagnosis->subjectLabel() }}</p>
        <p class="font-semibold">{{ $viewerLine }}</p>
    </div>

    <x-ui.notice>{{ $diagnosis->status === \App\Enums\DiagnosisStatus::Published ? __('admin.diagnoses.preview.published') : __('admin.diagnoses.preview.not_published') }}</x-ui.notice>

    <section class="flex flex-col gap-3" aria-labelledby="preview-frame-title">
        <x-ui.section-header :title="__('admin.diagnoses.preview.frame')" id="preview-frame-title">
            <x-slot:actions>
                <x-ui.segmented :label="__('admin.publish.device')">
                    @foreach (['phone', 'desktop'] as $option)
                        <x-ui.segment :selected="$device === $option" wire:click="$set('device', '{{ $option }}')">{{ __('admin.publish.devices.'.$option) }}</x-ui.segment>
                    @endforeach
                </x-ui.segmented>
            </x-slot:actions>
        </x-ui.section-header>
        <iframe
            src="{{ route('admin.main-diagnoses.preview', $diagnosis) }}"
            title="{{ __('admin.diagnoses.preview.frame') }}"
            @class(['h-[44rem] rounded-card border border-grey-250 bg-white', 'w-[390px] max-w-full' => $device === 'phone', 'w-full' => $device === 'desktop'])
        ></iframe>
    </section>
</div>
