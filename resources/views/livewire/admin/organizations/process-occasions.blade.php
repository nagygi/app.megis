{{-- The occasions of one process (12 · 12.6). --}}
<div class="flex flex-col gap-6">
    <x-ui.flash />

    <div class="flex flex-col gap-1">
        <a href="{{ route('admin.organizations.processes', $organization) }}" class="text-sm font-semibold">← {{ __('admin.organizations.tabs.processes') }}</a>
        <x-admin.page-header :title="$process->name" class="mb-0" />
        <p class="text-grey-700">{{ $process->kind->label() }}@if ($process->rhythm) · {{ $process->rhythm->label() }}@endif</p>
    </div>

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
                        <span class="block text-xs text-grey-600">
                            {{ __('admin.occasions.sources.'.$occasion->source) }}
                            @if ($occasion->process_id !== $process->id)
                                · {{ $occasion->process->name }}
                            @endif
                        </span>
                    </td>
                    <td class="px-3 py-2">
                        @if ($occasion->materials->isEmpty())
                            <span class="text-grey-600">{{ __('admin.occasions.no_materials') }}</span>
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
                <tr><td colspan="3" class="px-3 py-6 text-center text-grey-600">{{ __('admin.common.empty') }}</td></tr>
            @endforelse
        </x-admin.table>
    </section>

    @if ($main)
        <form wire:submit="storeOccasion" class="flex max-w-2xl flex-col gap-4 rounded-card border border-grey-250 bg-white p-6">
            <h2 class="text-lg font-semibold">{{ __('admin.occasions.add') }}</h2>
            <p class="text-sm text-grey-700">{{ __('admin.occasions.add_help', ['diagnosis' => $main->diagnosis->name]) }}</p>
            <div class="flex flex-col gap-1.5">
                <label for="admin-occasion-file" class="text-sm font-medium">{{ __('processes.occasions.file') }}</label>
                <input id="admin-occasion-file" type="file" wire:model="file" aria-describedby="admin-occasion-file-help" class="text-base">
                <p id="admin-occasion-file-help" class="text-sm text-grey-600">{{ __('admin.upload.rules', $rules) }}</p>
                <div wire:loading wire:target="file" class="text-sm" role="status">{{ __('admin.upload.uploading') }}</div>
                <x-form.error name="file" id="file" />
            </div>
            <x-form.input name="date" type="date" wire:model="date" :label="__('processes.occasions.when')" :max="today()->toDateString()" required />
            <x-form.checkbox name="consent" wire:model="consent" :label="__('processes.occasions.consent')" :help="__('processes.occasions.consent_help')" />
            <x-ui.button type="submit" class="self-start" wire:loading.attr="disabled" wire:target="file,storeOccasion">{{ __('admin.upload.submit') }}</x-ui.button>
        </form>
    @else
        <p class="text-sm text-grey-600">{{ __('admin.occasions.no_diagnosis') }}</p>
    @endif
</div>
