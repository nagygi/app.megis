{{-- Publikálás (A4, 21): preview · checklist · publish · corrections · withdrawal. --}}
@php
    $links = [
        'rows' => route('admin.main-diagnoses.reading', $diagnosis),
        'claims' => route('admin.main-diagnoses.reading', $diagnosis).'#claims-title',
        'classified' => route('admin.organizations.processes', $diagnosis->organization),
        'threshold' => route('admin.main-diagnoses.run', $diagnosis),
    ];
@endphp

<div class="flex max-w-7xl flex-col gap-6">
    <x-admin.main-diagnosis-tabs :diagnosis="$diagnosis" />

    <x-ui.flash />

    {{-- Ellenőrzőlista (21 · 21.2) --}}
    <section class="flex flex-col gap-2 rounded-card border border-grey-250 bg-white p-5" aria-labelledby="checklist-title">
        <h2 id="checklist-title" class="text-lg font-semibold">{{ __('admin.publish.checklist') }}</h2>
        <ul class="flex flex-col gap-1">
            @foreach ($checklist as $key => $done)
                <li class="flex items-center gap-2">
                    <x-ui.badge :tone="$done ? 'green' : 'coral'">{{ $done ? __('admin.publish.done') : __('admin.publish.missing') }}</x-ui.badge>
                    @if ($done)
                        <span>{{ __('admin.publish.items.'.$key) }}</span>
                    @else
                        <a href="{{ $links[$key] }}" class="font-semibold">{{ __('admin.publish.items.'.$key) }}</a>
                    @endif
                </li>
            @endforeach
        </ul>

        @if ($isReading)
            <div class="pt-2">
                @if ($canPublish)
                    <x-ui.button tone="coral" wire:click="publish" wire:confirm="{{ $snapshots->isEmpty() ? __('admin.publish.confirm') : __('admin.publish.confirm_republish') }}">{{ __('admin.publish.publish') }}</x-ui.button>
                @else
                    <x-ui.button tone="coral" disabled aria-disabled="true">{{ __('admin.publish.publish') }}</x-ui.button>
                @endif
            </div>
        @elseif (! $isPublished)
            <p class="text-sm text-grey-600">{{ __('admin.publish.not_reading') }}</p>
        @endif
    </section>

    {{-- Előnézet (21 · 21.1) --}}
    <section class="flex flex-col gap-3" aria-labelledby="preview-title">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="preview-title" class="text-lg font-semibold">{{ __('admin.publish.preview') }}</h2>
            <div class="flex gap-2 text-sm" role="group" aria-label="{{ __('admin.publish.device') }}">
                @foreach (['phone', 'desktop'] as $option)
                    <button type="button" wire:click="$set('device', '{{ $option }}')" @class(['rounded-pill px-3 py-1 font-semibold', 'bg-charcoal text-cream' => $device === $option, 'border border-charcoal' => $device !== $option]) aria-pressed="{{ $device === $option ? 'true' : 'false' }}">{{ __('admin.publish.devices.'.$option) }}</button>
                @endforeach
            </div>
        </div>
        <div class="grid gap-4 xl:grid-cols-2">
            <div class="flex flex-col gap-2">
                <h3 class="font-semibold">{{ __('admin.publish.leader_view') }}</h3>
                <iframe
                    src="{{ route('admin.main-diagnoses.preview', $diagnosis) }}"
                    title="{{ __('admin.publish.leader_view') }}"
                    @class(['h-[44rem] rounded-card border border-grey-250 bg-white', 'w-[390px] max-w-full' => $device === 'phone', 'w-full' => $device === 'desktop'])
                ></iframe>
            </div>
            @if ($recipients->isNotEmpty())
                <div class="flex flex-col gap-2">
                    <div class="flex flex-wrap items-end justify-between gap-2">
                        <h3 class="font-semibold">{{ __('admin.publish.takeaway') }}</h3>
                        <x-form.select name="peerId" wire:model.live="peerId" :label="__('admin.publish.peer')">
                            @foreach ($recipients as $recipient)
                                <option value="{{ $recipient->id }}">{{ $recipient->name }}</option>
                            @endforeach
                        </x-form.select>
                    </div>
                    @if ($peerId)
                        <iframe
                            wire:key="takeaway-{{ $peerId }}"
                            src="{{ route('admin.main-diagnoses.preview-takeaway', [$diagnosis, $peerId]) }}"
                            title="{{ __('admin.publish.takeaway') }}"
                            @class(['h-[44rem] rounded-card border border-grey-250 bg-white', 'w-[390px] max-w-full' => $device === 'phone', 'w-full' => $device === 'desktop'])
                        ></iframe>
                    @endif
                </div>
            @elseif ($main->hasPeers())
                <p class="text-sm text-grey-600">{{ __('admin.publish.no_takeaways') }}</p>
            @endif
        </div>
    </section>

    @if ($isPublished)
        <div class="grid gap-4 lg:grid-cols-2">
            {{-- Javítás publikálás után (21 · 21.4) --}}
            <form wire:submit="publishCorrection" class="flex flex-col gap-3 rounded-card border border-grey-250 bg-white p-5">
                <h2 class="text-lg font-semibold">{{ __('admin.publish.correction') }}</h2>
                <p class="text-sm text-grey-700">{{ __('admin.publish.correction_help') }}</p>
                <x-form.input name="correctionNote" wire:model="correctionNote" :label="__('admin.publish.correction_note')" />
                <x-ui.button type="submit" class="self-start">{{ __('admin.publish.correction_button') }}</x-ui.button>
            </form>

            {{-- Visszavonás (21 · 21.5) --}}
            <form wire:submit="withdraw" wire:confirm="{{ __('admin.publish.confirm_withdraw') }}" class="flex flex-col gap-3 rounded-card border border-grey-250 bg-white p-5">
                <h2 class="text-lg font-semibold">{{ __('admin.publish.withdraw') }}</h2>
                <p class="text-sm text-grey-700">{{ __('admin.publish.withdraw_help') }}</p>
                <x-form.textarea name="withdrawReason" wire:model="withdrawReason" :label="__('admin.publish.withdraw_reason')" rows="2" required />
                <x-ui.button type="submit" tone="outline" class="self-start">{{ __('admin.publish.withdraw_button') }}</x-ui.button>
            </form>
        </div>
    @endif

    @if ($snapshots->isNotEmpty())
        <section class="flex flex-col gap-2" aria-labelledby="snapshots-title">
            <h2 id="snapshots-title" class="text-lg font-semibold">{{ __('admin.publish.snapshots') }}</h2>
            <ul class="flex flex-col gap-1 text-sm text-grey-700">
                @foreach ($snapshots as $snapshot)
                    <li>{{ $snapshot->sequence }}. · {{ $snapshot->created_at->format('Y.m.d. H:i') }} · {{ __('admin.publish.reasons.'.$snapshot->reason) }}@if ($snapshot->note) · {{ $snapshot->note }}@endif</li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
