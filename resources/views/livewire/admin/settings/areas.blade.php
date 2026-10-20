{{-- Beállítások → Területek (18 · 18.1). --}}
<div class="flex max-w-3xl flex-col gap-6">
    <x-ui.flash />

    <x-admin.page-header :title="__('admin.areas.title')" :description="__('admin.areas.lead')" :spaced="false" />

    <ul class="flex flex-col gap-3">
        @foreach ($areas as $area)
            <x-ui.card as="li" bordered wire:key="area-{{ $area->id }}">
                @if ($editingId === $area->id)
                    <form wire:submit="save" class="flex flex-col gap-4">
                        <x-form.input name="name" wire:model="name" :label="__('admin.areas.name')" required />
                        <x-form.textarea name="whatWeLookAt" wire:model="whatWeLookAt" :label="__('admin.areas.what_we_look_at')" :help="__('admin.areas.what_we_look_at_help')" rows="2" />
                        <x-form.textarea name="trace" wire:model="trace" :label="__('admin.areas.trace')" :help="__('admin.areas.trace_help')" rows="2" />
                        <x-form.textarea name="sixDescription" wire:model="sixDescription" :label="__('admin.areas.six_description')" :help="__('admin.areas.six_description_help')" rows="2" />
                        <div class="flex flex-wrap gap-3">
                            <x-ui.button type="submit">{{ __('admin.common.save') }}</x-ui.button>
                            <x-ui.button tone="outline" wire:click="cancel">{{ __('admin.common.cancel') }}</x-ui.button>
                        </div>
                    </form>
                @else
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <dl class="grid flex-1 gap-2 text-sm md:grid-cols-[10rem_1fr]">
                            <dt class="sr-only">{{ __('admin.areas.name') }}</dt>
                            <dd class="text-base font-semibold md:col-span-2">{{ $area->name }}</dd>
                            <dt class="text-grey-700">{{ __('admin.areas.what_we_look_at') }}</dt>
                            <dd>{{ $area->what_we_look_at ?? '–' }}</dd>
                            <dt class="text-grey-700">{{ __('admin.areas.trace') }}</dt>
                            <dd>{{ $area->trace ?? '–' }}</dd>
                            <dt class="text-grey-700">{{ __('admin.areas.six_description') }}</dt>
                            <dd>{{ $area->six_description ?? '–' }}</dd>
                        </dl>
                        <x-ui.button tone="outline" size="sm" wire:click="edit({{ $area->id }})" aria-label="{{ __('admin.common.edit') }}: {{ $area->name }}">{{ __('admin.common.edit') }}</x-ui.button>
                    </div>
                @endif
            </x-ui.card>
        @endforeach
    </ul>
</div>
