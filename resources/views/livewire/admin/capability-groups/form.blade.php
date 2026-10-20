<div class="max-w-3xl">
    <x-admin.page-header :title="$title" />

    <x-ui.card as="form" bordered padding="lg" wire:submit="save" class="flex flex-col gap-5">
        <x-form.input name="name" wire:model="name" :label="__('admin.capability_groups.name')" required />
        <x-form.input name="name_en" wire:model="name_en" :label="__('admin.capability_groups.name_en')" />
        <x-form.textarea name="description" wire:model="description" :label="__('admin.capability_groups.description')" />

        <div class="flex flex-wrap gap-3">
            <x-ui.button type="submit">{{ __('admin.common.save') }}</x-ui.button>
            <x-ui.button tone="outline" :href="route('admin.capability-groups.index')">{{ __('admin.common.cancel') }}</x-ui.button>
        </div>
    </x-ui.card>
</div>
