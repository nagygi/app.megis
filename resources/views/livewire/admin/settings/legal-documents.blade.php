<div class="flex max-w-3xl flex-col gap-6">
    <x-ui.flash />

    <x-admin.page-header :title="__('admin.legal.title')" :spaced="false" />

    @foreach ($documents as $document)
        @php($type = $document['type'])
        <section class="rounded-card border border-grey-250 bg-white p-6" wire:key="legal-{{ $type->value }}">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">{{ $type->label() }}</h2>
                    <p class="text-sm text-grey-600">/{{ $type->value }}</p>
                </div>
                @if ($uploadingType !== $type->value)
                    <x-ui.button size="sm" wire:click="openUpload('{{ $type->value }}')">{{ __('admin.legal.upload') }}</x-ui.button>
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
                    <p class="text-sm text-grey-600">{{ __('admin.legal.uploaded', ['name' => $current->creator?->name ?? '–', 'date' => $current->created_at->format('Y.m.d. H:i')]) }}</p>
                    <a href="{{ route('admin.legal.version', $current) }}" target="_blank" class="text-sm font-semibold text-charcoal">{{ __('admin.legal.open') }}</a>
                @else
                    <p class="rounded-field bg-yellow-pale px-3 py-2" role="alert">{{ __('admin.legal.missing') }}</p>
                @endif
            </div>

            @if ($uploadingType === $type->value)
                <form wire:submit="storeVersion" class="mt-6 flex flex-col gap-5 border-t border-grey-200 pt-6">
                    <div class="flex flex-col gap-1.5">
                        <x-form.label for="legal-file" required>{{ __('admin.legal.file') }}</x-form.label>
                        <input id="legal-file" type="file" wire:model="file" accept="application/pdf" aria-describedby="legal-file-help" class="text-sm">
                        <p id="legal-file-help" class="text-sm text-grey-600">{{ __('admin.legal.file_help') }}</p>
                        <div wire:loading wire:target="file" class="text-sm text-grey-700">…</div>
                        <x-form.error name="file" id="legal-file" />
                    </div>

                    <x-form.input name="effectiveFrom" type="date" wire:model="effectiveFrom" :label="__('admin.legal.effective_from')" :help="__('admin.legal.effective_help')" required />

                    @if ($type->requiresAcceptance())
                        <x-form.toggle name="requiresReacceptance" wire:model="requiresReacceptance" :label="__('admin.legal.requires_reacceptance')" :help="__('admin.legal.requires_reacceptance_help')" />
                    @endif

                    <div class="flex gap-3">
                        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="file,storeVersion">{{ __('admin.legal.upload') }}</x-ui.button>
                        <x-ui.button tone="outline" wire:click="closeUpload">{{ __('admin.common.cancel') }}</x-ui.button>
                    </div>
                </form>
            @endif

            <details class="mt-6">
                <summary class="cursor-pointer text-sm font-semibold">{{ __('admin.legal.previous') }}</summary>
                <ul class="mt-3 flex flex-col gap-1 text-sm">
                    @forelse ($document['versions']->reject(fn ($version) => $document['current']?->is($version)) as $version)
                        <li>
                            <a href="{{ route('admin.legal.version', $version) }}" target="_blank" class="text-charcoal">{{ $version->original_name }}</a>
                            · {{ $version->effective_from->format('Y.m.d.') }}
                            @if ($version->effective_from->isFuture()) <x-ui.badge tone="yellow">{{ __('admin.legal.scheduled') }}</x-ui.badge> @endif
                            <span class="text-grey-600">· {{ $version->creator?->name ?? '–' }}</span>
                        </li>
                    @empty
                        <li class="text-grey-600">{{ __('admin.legal.no_previous') }}</li>
                    @endforelse
                </ul>
            </details>
        </section>
    @endforeach
</div>
