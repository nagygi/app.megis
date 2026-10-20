@php($fieldLabel = fn (string $field) => __('admin.capabilities.fields.'.$field))

<div class="flex max-w-3xl flex-col gap-6">
    <x-ui.flash />

    <x-admin.page-header :title="__('admin.capabilities.import.title')" :spaced="false" />

    <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-4">
        <p>{{ __('admin.capabilities.import.lead') }}</p>
        <p class="text-sm text-grey-700">{{ __('admin.capabilities.import.rules') }}</p>

        {{-- novalidate: the file is checked on the server, after Livewire has uploaded it. --}}
        <form wire:submit="showPreview" class="flex flex-col gap-4" novalidate>
            <div class="flex flex-col gap-1.5">
                <x-form.file
                    name="file"
                    id="capability-file"
                    wire:model="file"
                    accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                    :label="__('admin.capabilities.import.file')"
                    required
                />
                <p wire:loading wire:target="file" class="text-sm text-grey-700">{{ __('admin.capabilities.import.uploading') }}</p>
            </div>

            @if ($preview === null)
                <div>
                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="file,showPreview">{{ __('admin.capabilities.import.preview_button') }}</x-ui.button>
                </div>
            @endif
        </form>
    </x-ui.card>

    @if ($preview !== null)
        {{-- Előnézet: new, changed, unchanged, with the changed fields (07 · 7.6) --}}
        <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-5" aria-labelledby="import-preview">
            <h2 id="import-preview" class="text-lg font-semibold">{{ __('admin.capabilities.import.preview_title') }}</h2>
            <p class="text-lg">{{ __('admin.capabilities.import.summary', ['created' => count($preview['created']), 'updated' => count($preview['updated']), 'unchanged' => count($preview['unchanged'])]) }}</p>

            @if ($preview['errors'] !== [])
                <x-ui.notice tone="error" :title="__('admin.capabilities.import.errors_title')">
                    <ul class="list-disc pl-5 text-sm">
                        @foreach ($preview['errors'] as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </x-ui.notice>
            @endif

            @if ($preview['warnings'] !== [])
                <x-ui.notice tone="warning" :title="__('admin.capabilities.import.warnings_title')">
                    <ul class="list-disc pl-5 text-sm">
                        @foreach ($preview['warnings'] as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </x-ui.notice>
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
                {{-- Bordered: the disclosure is white like the card around it. --}}
                <x-ui.disclosure :summary="__('admin.capabilities.import.unchanged_title', ['count' => count($preview['unchanged'])])" class="border border-grey-250">
                    <p class="text-sm text-grey-700">{{ implode(', ', $preview['unchanged']) }}</p>
                </x-ui.disclosure>
            @endif

            <div class="flex flex-wrap items-center gap-3">
                @if ($preview['created'] !== [] || $preview['updated'] !== [] || $preview['archived'] !== [] || $preview['restored'] !== [])
                    <x-ui.button wire:click="load" wire:loading.attr="disabled" wire:target="load">{{ __('admin.capabilities.import.load_button') }}</x-ui.button>
                @else
                    <p class="text-grey-700">{{ __('admin.capabilities.import.nothing') }}</p>
                @endif
                <x-ui.button tone="outline" wire:click="cancel">{{ __('admin.common.cancel') }}</x-ui.button>
            </div>
        </x-ui.card>
    @endif

    <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-3" aria-labelledby="import-history">
        <h2 id="import-history" class="text-lg font-semibold">{{ __('admin.capabilities.import.history') }}</h2>
        @forelse ($imports as $import)
            <p wire:key="import-{{ $import->id }}" class="text-sm">
                {{ $import->created_at->format('Y.m.d. H:i') }} · {{ $import->creator?->name ?? __('admin.versions.system') }} · {{ $import->file_name }}
                <span class="block text-grey-700">{{ __('admin.capabilities.import.summary', ['created' => $import->created_count, 'updated' => $import->updated_count, 'unchanged' => $import->unchanged_count]) }}@if ($import->skipped_count > 0) · {{ __('admin.capabilities.import.skipped', ['count' => $import->skipped_count]) }}@endif</span>
            </p>
        @empty
            <p class="text-grey-700">{{ __('admin.capabilities.import.no_history') }}</p>
        @endforelse
    </x-ui.card>
</div>
