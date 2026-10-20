{{-- The occasions of one process (12 · 12.6). --}}
<div class="flex flex-col gap-6">
    <x-ui.flash />

    <x-admin.page-header
        :title="$process->name"
        :description="$process->kind->label().($process->rhythm ? ' · '.$process->rhythm->label() : '')"
        :back="route('admin.organizations.processes', $organization)"
        :back-label="__('admin.organizations.tabs.processes')"
        :spaced="false"
    />

    <section class="flex flex-col gap-2" aria-labelledby="occasions-title">
        <h2 id="occasions-title" class="text-lg font-semibold">{{ __('admin.occasions.title') }}</h2>
        <x-admin.table>
            <x-slot:head>
                <x-admin.th>{{ __('admin.occasions.date') }}</x-admin.th>
                <x-admin.th>{{ __('admin.occasions.materials') }}</x-admin.th>
                <x-admin.th>{{ __('admin.occasions.experiences') }}</x-admin.th>
            </x-slot:head>

            @forelse ($occasions as $occasion)
                <tr wire:key="occasion-{{ $occasion->id }}" class="align-top">
                    <td class="px-3 py-2">
                        <span class="font-semibold">{{ $occasion->occurred_at->format('Y.m.d.') }}</span>
                        <span class="block text-xs text-grey-700">
                            {{ __('admin.occasions.sources.'.$occasion->source) }}
                            @if ($occasion->process_id !== $process->id)
                                · {{ $occasion->process->name }}
                            @endif
                        </span>
                    </td>
                    <td class="px-3 py-2">
                        @if ($occasion->materials->isEmpty())
                            <span class="text-grey-700">{{ __('admin.occasions.no_materials') }}</span>
                        @else
                            <ul class="flex flex-col gap-1">
                                @foreach ($occasion->materials as $material)
                                    <li wire:key="occasion-material-{{ $material->uuid }}">
                                        <a href="{{ route('admin.materials.show', $material) }}" class="font-semibold">{{ $material->is_situation ? __('processes.occasions.situation') : ($material->original_name ?? $material->source->label()) }}</a>
                                        · {{ $material->user?->name ?? $material->creator?->name ?? '–' }}
                                        · <span @class(['text-coral-ink' => $material->status === \App\Enums\MaterialStatus::Failed])>{{ $material->status->label() }}</span>
                                        @if ($material->durationLabel())
                                            · {{ $material->durationLabel() }}
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </td>
                    <td class="px-3 py-2">{{ $occasion->experiences_count }}</td>
                </tr>
            @empty
                <x-admin.empty-row :colspan="3">{{ __('admin.occasions.empty') }}</x-admin.empty-row>
            @endforelse
        </x-admin.table>
    </section>

    @if ($main)
        <x-ui.card as="form" bordered padding="lg" wire:submit="storeOccasion" class="flex max-w-3xl flex-col gap-4">
            <h2 class="text-lg font-semibold">{{ __('admin.occasions.add') }}</h2>
            <p class="text-sm text-grey-700">{{ __('admin.occasions.add_help', ['diagnosis' => $main->diagnosis->name]) }}</p>
            <div class="flex flex-col gap-1.5">
                <x-form.file name="file" id="admin-occasion-file" wire:model="file" :label="__('processes.occasions.file')" :help="__('admin.upload.rules', $rules)" />
                <div wire:loading wire:target="file" class="text-sm text-grey-700" role="status">{{ __('admin.upload.uploading') }}</div>
            </div>
            <x-form.input name="date" type="date" wire:model="date" :label="__('processes.occasions.when')" :max="today()->toDateString()" required />
            <x-form.checkbox name="consent" wire:model="consent" :label="__('processes.occasions.consent')" :help="__('processes.occasions.consent_help')" />
            <x-ui.button type="submit" icon="upload" class="self-start" wire:loading.attr="disabled" wire:target="file,storeOccasion">{{ __('admin.upload.submit') }}</x-ui.button>
        </x-ui.card>
    @else
        <p class="text-sm text-grey-700">{{ __('admin.occasions.no_diagnosis') }}</p>
    @endif
</div>
