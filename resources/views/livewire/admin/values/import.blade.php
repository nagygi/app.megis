@php
    $fieldLabel = fn (string $field) => __('admin.values.fields.'.$field);
@endphp

<div class="flex max-w-3xl flex-col gap-6">
    <x-ui.flash />

    <x-admin.page-header :title="__('admin.values.import.title')" :spaced="false" />

    <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-4">
        @unless ($canImport)
            {{-- Only a superadmin loads (03 · 3.6); the page stays readable. --}}
            <x-ui.notice tone="warning">{{ __('admin.values.import.superadmin_only') }}</x-ui.notice>
        @endunless

        <p>{{ __('admin.values.import.lead') }}</p>
        <p class="text-sm text-grey-700">{{ __('admin.values.import.rules') }}</p>

        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <x-ui.button tone="outline" size="sm" :href="route('admin.values.import.sample')">{{ __('admin.values.import.sample') }}</x-ui.button>
            <span class="text-sm text-grey-700">{{ __('admin.values.import.sample_hint') }}</span>
        </div>

        {{-- novalidate: the file is checked on the server, after Livewire has uploaded it. --}}
        <form wire:submit="showPreview" class="flex flex-col gap-4" novalidate>
            <div class="flex flex-col gap-1.5">
                <x-form.file
                    name="file"
                    id="value-file"
                    wire:model="file"
                    accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv"
                    :label="__('admin.values.import.file')"
                    :disabled="! $canImport"
                    required
                />
                <p wire:loading wire:target="file" class="text-sm text-grey-700">{{ __('admin.values.import.uploading') }}</p>
            </div>

            @if ($preview === null)
                <div>
                    <x-ui.button type="submit" :disabled="! $canImport" wire:loading.attr="disabled" wire:target="file,showPreview">{{ __('admin.values.import.preview_button') }}</x-ui.button>
                </div>
            @endif
        </form>
    </x-ui.card>

    @if ($preview !== null)
        {{-- Előnézet: new, changed, unchanged, with the changed fields (03 · 3.6) --}}
        <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-5" aria-labelledby="import-preview">
            <h2 id="import-preview" class="text-lg font-semibold">{{ __('admin.values.import.preview_title') }}</h2>

            @if ($preview['errors'] !== [])
                {{-- One invalid row stops the whole load, so there is nothing to count. --}}
                <x-ui.notice tone="error" :title="__('admin.values.import.errors_title')">
                    <p class="text-sm">{{ __('admin.values.import.failed') }}</p>
                    <ul class="list-disc pl-5 text-sm">
                        @foreach ($preview['errors'] as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </x-ui.notice>
            @else
                <p class="text-lg">{{ __('admin.values.import.summary', ['created' => count($preview['created']), 'updated' => count($preview['updated']), 'unchanged' => count($preview['unchanged'])]) }}</p>
            @endif

            @if ($preview['warnings'] !== [])
                <x-ui.notice tone="warning" :title="__('admin.values.import.warnings_title')">
                    <ul class="list-disc pl-5 text-sm">
                        @foreach ($preview['warnings'] as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </x-ui.notice>
            @endif

            @if ($preview['created'] !== [])
                <div class="flex flex-col gap-2">
                    <h3 class="font-semibold">{{ __('admin.values.import.created_title') }}</h3>
                    <p class="text-sm text-grey-700">{{ implode(', ', $preview['created']) }}</p>
                </div>
            @endif

            @if ($preview['updated'] !== [])
                <div class="flex flex-col gap-2">
                    <h3 class="font-semibold">{{ __('admin.values.import.updated_title') }}</h3>
                    <ul class="flex flex-col gap-2">
                        @foreach ($preview['updated'] as $row)
                            <li>
                                {{ $row['name'] }}
                                <span class="block text-sm text-grey-700">{{ collect($row['fields'])->map(fn ($field) => $fieldLabel($field))->implode(', ') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @foreach (['reviewed' => 'reviewed_title', 'createdCategories' => 'categories_created_title', 'updatedCategories' => 'categories_updated_title', 'ignoredColumns' => 'ignored_title'] as $key => $title)
                @if ($preview[$key] !== [])
                    <div class="flex flex-col gap-1">
                        <h3 class="font-semibold">{{ __('admin.values.import.'.$title) }}</h3>
                        <p class="text-sm text-grey-700">{{ implode(', ', $preview[$key]) }}</p>
                    </div>
                @endif
            @endforeach

            @if ($preview['unchanged'] !== [])
                {{-- Bordered: the disclosure is white like the card around it. --}}
                <x-ui.disclosure :summary="__('admin.values.import.unchanged_title', ['count' => count($preview['unchanged'])])" class="border border-grey-250">
                    <p class="text-sm text-grey-700">{{ implode(', ', $preview['unchanged']) }}</p>
                </x-ui.disclosure>
            @endif

            <div class="flex flex-wrap items-center gap-3">
                @if ($preview['errors'] === [] && $preview['hasChanges'])
                    <x-ui.button wire:click="load" wire:loading.attr="disabled" wire:target="load">{{ __('admin.values.import.load_button') }}</x-ui.button>
                @elseif ($preview['errors'] === [])
                    <p class="text-grey-700">{{ __('admin.values.import.nothing') }}</p>
                @endif
                <x-ui.button tone="outline" wire:click="cancel">{{ __('admin.common.cancel') }}</x-ui.button>
            </div>
        </x-ui.card>
    @endif

    <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-3" aria-labelledby="import-history">
        <h2 id="import-history" class="text-lg font-semibold">{{ __('admin.values.import.history') }}</h2>
        @forelse ($imports as $import)
            <p wire:key="import-{{ $import->id }}" class="text-sm">
                {{ $import->created_at->format('Y.m.d. H:i') }} · {{ $import->creator?->name ?? __('admin.versions.system') }} · {{ $import->file_name }}
                <span class="block text-grey-700">{{ __('admin.values.import.summary', ['created' => $import->created_count, 'updated' => $import->updated_count, 'unchanged' => $import->unchanged_count]) }}@if ($import->categories_created_count > 0) · {{ __('admin.values.import.history_categories', ['count' => $import->categories_created_count]) }}@endif</span>
            </p>
        @empty
            <p class="text-grey-700">{{ __('admin.values.import.no_history') }}</p>
        @endforelse
    </x-ui.card>
</div>
