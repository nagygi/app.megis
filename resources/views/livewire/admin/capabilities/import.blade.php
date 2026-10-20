@php($fieldLabel = fn (string $field) => __('admin.capabilities.fields.'.$field))

<div class="flex max-w-4xl flex-col gap-6">
    <x-ui.flash />

    <x-admin.page-header :title="__('admin.capabilities.import.title')" />

    <section class="flex flex-col gap-4 rounded-card border border-grey-250 bg-white p-6">
        <p>{{ __('admin.capabilities.import.lead') }}</p>
        <p class="text-sm text-grey-600">{{ __('admin.capabilities.import.rules') }}</p>

        <form wire:submit="showPreview" class="flex flex-col gap-4">
            <div class="flex flex-col gap-1.5">
                <x-form.label for="capability-file" required>{{ __('admin.capabilities.import.file') }}</x-form.label>
                <input
                    id="capability-file"
                    type="file"
                    wire:model="file"
                    accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                    @error('file') aria-invalid="true" aria-describedby="capability-file-error" @enderror
                    class="text-base"
                >
                <x-form.error name="file" id="capability-file" />
                <p wire:loading wire:target="file" class="text-sm text-grey-600">{{ __('admin.capabilities.import.uploading') }}</p>
            </div>

            @if ($preview === null)
                <div>
                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="file,showPreview">{{ __('admin.capabilities.import.preview_button') }}</x-ui.button>
                </div>
            @endif
        </form>
    </section>

    @if ($preview !== null)
        {{-- Előnézet: new, changed, unchanged, with the changed fields (07 · 7.6) --}}
        <section class="flex flex-col gap-5 rounded-card border border-grey-250 bg-white p-6" aria-labelledby="import-preview">
            <h2 id="import-preview" class="text-lg font-semibold">{{ __('admin.capabilities.import.preview_title') }}</h2>
            <p class="text-lg">{{ __('admin.capabilities.import.summary', ['created' => count($preview['created']), 'updated' => count($preview['updated']), 'unchanged' => count($preview['unchanged'])]) }}</p>

            @if ($preview['errors'] !== [])
                <div class="flex flex-col gap-2 rounded-field bg-coral-light p-4">
                    <h3 class="font-semibold">{{ __('admin.capabilities.import.errors_title') }}</h3>
                    <ul class="list-disc pl-5 text-sm">
                        @foreach ($preview['errors'] as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($preview['warnings'] !== [])
                <div class="flex flex-col gap-2 rounded-field bg-yellow-pale p-4">
                    <h3 class="font-semibold">{{ __('admin.capabilities.import.warnings_title') }}</h3>
                    <ul class="list-disc pl-5 text-sm">
                        @foreach ($preview['warnings'] as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($preview['created'] !== [])
                <div class="flex flex-col gap-2">
                    <h3 class="font-semibold">{{ __('admin.capabilities.import.created_title') }}</h3>
                    <ul class="flex flex-col gap-1">
                        @foreach ($preview['created'] as $row)
                            <li><span class="font-mono text-sm">{{ $row['code'] }}</span> · {{ $row['name'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($preview['updated'] !== [])
                <div class="flex flex-col gap-2">
                    <h3 class="font-semibold">{{ __('admin.capabilities.import.updated_title') }}</h3>
                    <ul class="flex flex-col gap-2">
                        @foreach ($preview['updated'] as $row)
                            <li>
                                <span class="font-mono text-sm">{{ $row['code'] }}</span> · {{ $row['name'] }}
                                <span class="block text-sm text-grey-700">{{ collect($row['fields'])->map(fn ($field) => $fieldLabel($field))->implode(', ') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @foreach (['archived' => 'archived_title', 'restored' => 'restored_title', 'createdGroups' => 'groups_title', 'ignoredColumns' => 'ignored_title'] as $key => $title)
                @if ($preview[$key] !== [])
                    <div class="flex flex-col gap-1">
                        <h3 class="font-semibold">{{ __('admin.capabilities.import.'.$title) }}</h3>
                        <p class="text-sm text-grey-700">{{ implode(', ', $preview[$key]) }}</p>
                    </div>
                @endif
            @endforeach

            @if ($preview['unchanged'] !== [])
                <details>
                    <summary class="cursor-pointer font-semibold">{{ __('admin.capabilities.import.unchanged_title', ['count' => count($preview['unchanged'])]) }}</summary>
                    <p class="mt-2 text-sm text-grey-700">{{ implode(', ', $preview['unchanged']) }}</p>
                </details>
            @endif

            <div class="flex flex-wrap gap-3">
                @if ($preview['created'] !== [] || $preview['updated'] !== [] || $preview['archived'] !== [] || $preview['restored'] !== [])
                    <x-ui.button wire:click="load" wire:loading.attr="disabled" wire:target="load">{{ __('admin.capabilities.import.load_button') }}</x-ui.button>
                @else
                    <p class="text-grey-700">{{ __('admin.capabilities.import.nothing') }}</p>
                @endif
                <x-ui.button tone="outline" wire:click="cancel">{{ __('admin.common.cancel') }}</x-ui.button>
            </div>
        </section>
    @endif

    <section class="flex flex-col gap-3 rounded-card border border-grey-250 bg-white p-6" aria-labelledby="import-history">
        <h2 id="import-history" class="text-lg font-semibold">{{ __('admin.capabilities.import.history') }}</h2>
        @forelse ($imports as $import)
            <p wire:key="import-{{ $import->id }}" class="text-sm">
                {{ $import->created_at->format('Y.m.d. H:i') }} · {{ $import->creator?->name ?? __('admin.versions.system') }} · {{ $import->file_name }}
                <span class="block text-grey-600">{{ __('admin.capabilities.import.summary', ['created' => $import->created_count, 'updated' => $import->updated_count, 'unchanged' => $import->unchanged_count]) }}@if ($import->skipped_count > 0) · {{ __('admin.capabilities.import.skipped', ['count' => $import->skipped_count]) }}@endif</span>
            </p>
        @empty
            <p class="text-grey-600">{{ __('admin.capabilities.import.no_history') }}</p>
        @endforelse
    </section>
</div>
