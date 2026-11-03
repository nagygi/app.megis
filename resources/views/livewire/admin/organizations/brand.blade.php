@php
    $symbols = ['purpose' => 'trunk', 'value_proposition' => 'fruit', 'impact' => 'shade'];
@endphp

<div class="flex flex-col gap-6">
    <x-admin.page-header :title="__('admin.organizations.tabs.brand')" :spaced="false" />
    <x-ui.flash />

    @if ($viewed)
        {{-- A saved version, read-only (08 · 8.5) --}}
        <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-4">
            <x-ui.section-header :title="__('admin.versions.readonly', ['version' => $viewed->label()])">
                <x-slot:actions>
                    <x-ui.button tone="outline" size="sm" icon="chevron-left" wire:click="$set('viewing', null)">{{ __('admin.versions.back_to_list') }}</x-ui.button>
                </x-slot:actions>
            </x-ui.section-header>
            <p class="text-sm text-grey-700">{{ $viewed->created_at->format('Y.m.d. H:i') }} · {{ $viewed->creator?->name ?? __('admin.versions.system') }}</p>

            <dl class="grid gap-2 text-sm md:grid-cols-[14rem_1fr]">
                <dt class="font-medium">{{ __('admin.value_sets.brand.root') }}</dt>
                <dd>{{ collect($viewed->snapshot['values'] ?? [])->pluck('name')->implode(' · ') ?: __('admin.common.none') }}</dd>
                @foreach (\App\Models\OrganizationValueSet::BRAND_FIELDS as $field)
                    <dt class="font-medium">{{ __('admin.value_sets.brand.'.$field) }}</dt>
                    <dd>{{ filled($viewed->snapshot[$field] ?? null) ? $viewed->snapshot[$field] : __('admin.common.none') }}</dd>
                @endforeach
            </dl>
        </x-ui.card>
    @else
        @if ($hasVersion)
            <x-ui.notice role="note">{{ __('admin.value_sets.change_warning') }}</x-ui.notice>
        @endif

        {{-- The brand base (08 · 8.2) --}}
        <form wire:submit="save" class="flex flex-col gap-6">
            <div class="grid gap-4 md:grid-cols-2">
                <x-ui.card bordered class="flex flex-col gap-2">
                    <h2 class="flex items-center gap-2 font-semibold"><x-megis.symbol name="root" class="size-6" /> {{ __('admin.value_sets.brand.root') }}</h2>
                    <p class="text-sm text-grey-700">{{ __('admin.value_sets.brand_questions.root') }}</p>
                    <p>{{ $names->implode(' · ') ?: __('admin.common.none') }}</p>
                    <x-ui.button tone="ghost" size="sm" class="-ml-1.5 self-start" :href="route('admin.organizations.values', $organization)">{{ __('admin.value_sets.values_link') }}</x-ui.button>
                </x-ui.card>

                @foreach ($symbols as $field => $symbol)
                    <x-ui.card bordered class="flex flex-col gap-2">
                        <h2 class="flex items-center gap-2 font-semibold"><x-megis.symbol :name="$symbol" class="size-6" /> {{ __('admin.value_sets.brand.'.$field) }}</h2>
                        <x-form.textarea
                            :name="$field"
                            wire:model="{{ $field }}"
                            :label="__('admin.value_sets.brand_questions.'.$field)"
                            required
                            rows="3"
                            maxlength="500"
                            :help="__('admin.value_sets.brand_help')"
                        />
                    </x-ui.card>
                @endforeach
            </div>

            <div>
                <x-ui.button type="submit">{{ __('admin.common.save') }}</x-ui.button>
            </div>
        </form>

        @include('livewire.admin.organizations.partials.value-set-versions')
    @endif
</div>
