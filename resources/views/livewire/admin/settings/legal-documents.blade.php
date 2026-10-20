<div class="flex max-w-3xl flex-col gap-6">
    <x-ui.flash />

    <x-admin.page-header :title="__('admin.legal.title')" :spaced="false" />

    @foreach ($documents as $document)
        @php($type = $document['type'])
        <x-ui.card as="section" bordered wire:key="legal-{{ $type->value }}">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">{{ $type->label() }}</h2>
                    <p class="text-sm text-grey-700">/{{ $type->value }}</p>
                </div>
                @if ($uploadingType !== $type->value)
                    <x-ui.button size="sm" icon="upload" wire:click="openUpload('{{ $type->value }}')">{{ __('admin.legal.upload') }}</x-ui.button>
                @endif
            </div>

            <div class="mt-4">
                @if ($document['current'])
                    @php($current = $document['current'])
                    <p class="text-sm font-medium">{{ __('admin.legal.current') }}</p>
                    <p>
                        {{ $current->original_name }} · {{ __('admin.legal.effective_from') }}: {{ $current->effective_from->format('Y.m.d.') }}
                        @if ($current->requires_reacceptance) <x-ui.badge tone="yellow">{{ __('admin.legal.requires_reacceptance') }}</x-ui.badge> @endif
                    </p>
                    <p class="text-sm text-grey-700">{{ __('admin.legal.uploaded', ['name' => $current->creator?->name ?? '–', 'date' => $current->created_at->format('Y.m.d. H:i')]) }}</p>
                    <x-ui.button tone="ghost" size="sm" icon="document" class="-ml-1.5" :href="route('admin.legal.version', $current)" target="_blank">{{ __('admin.legal.open') }}</x-ui.button>
                @else
                    <x-ui.notice tone="warning" role="alert">{{ __('admin.legal.missing') }}</x-ui.notice>
                @endif
            </div>

            @if ($uploadingType === $type->value)
                <form wire:submit="storeVersion" class="mt-6 flex flex-col gap-5 border-t border-grey-200 pt-6">
                    <div class="flex flex-col gap-1.5">
                        <x-form.file name="file" id="legal-file" wire:model="file" accept="application/pdf" :label="__('admin.legal.file')" :help="__('admin.legal.file_help')" required />
                        <div wire:loading wire:target="file" class="text-sm text-grey-700" role="status">{{ __('admin.upload.uploading') }}</div>
                    </div>

                    <x-form.input name="effectiveFrom" type="date" wire:model="effectiveFrom" :label="__('admin.legal.effective_from')" :help="__('admin.legal.effective_help')" required />

                    @if ($type->requiresAcceptance())
                        <x-form.toggle name="requiresReacceptance" wire:model="requiresReacceptance" :label="__('admin.legal.requires_reacceptance')" :help="__('admin.legal.requires_reacceptance_help')" />
                    @endif

                    <div class="flex flex-wrap gap-3">
                        <x-ui.button type="submit" icon="upload" wire:loading.attr="disabled" wire:target="file,storeVersion">{{ __('admin.legal.upload') }}</x-ui.button>
                        <x-ui.button tone="outline" wire:click="closeUpload">{{ __('admin.common.cancel') }}</x-ui.button>
                    </div>
                </form>
            @endif

            <details class="group mt-6">
                <summary class="inline-flex min-h-10 cursor-pointer list-none items-center gap-1 text-sm font-semibold [&::-webkit-details-marker]:hidden">
                    {{ __('admin.legal.previous') }}
                    <x-megis.icon name="chevron-down" class="transition-transform duration-200 group-open:rotate-180" />
                </summary>
                <ul class="mt-2 flex flex-col gap-1 text-sm">
                    @forelse ($document['versions']->reject(fn ($version) => $document['current']?->is($version)) as $version)
                        <li>
                            <a href="{{ route('admin.legal.version', $version) }}" target="_blank" class="text-charcoal">{{ $version->original_name }}</a>
                            · {{ $version->effective_from->format('Y.m.d.') }}
                            @if ($version->effective_from->isFuture()) <x-ui.badge tone="yellow">{{ __('admin.legal.scheduled') }}</x-ui.badge> @endif
                            <span class="text-grey-700">· {{ $version->creator?->name ?? '–' }}</span>
                        </li>
                    @empty
                        <li class="text-grey-700">{{ __('admin.legal.no_previous') }}</li>
                    @endforelse
                </ul>
            </details>
        </x-ui.card>
    @endforeach
</div>
