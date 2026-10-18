<div class="max-w-2xl">
    <x-admin.page-header :title="$title" />

    <form wire:submit="save" class="flex flex-col gap-5 rounded-card border border-grey-250 bg-white p-6">
        <x-form.input name="name" wire:model="name" :label="__('admin.value_categories.name')" required />
        <x-form.input name="name_en" wire:model="name_en" :label="__('admin.value_categories.name_en')" />
        <x-form.input name="higher_order" wire:model="higher_order" :label="__('admin.value_categories.higher_order')" :help="__('admin.value_categories.higher_order_help')" />
        <x-form.textarea name="description" wire:model="description" :label="__('admin.value_categories.description')" />

        <div class="flex flex-wrap gap-3">
            <x-ui.button type="submit">{{ __('admin.common.save') }}</x-ui.button>
            <x-ui.button tone="outline" :href="route('admin.value-categories.index')">{{ __('admin.common.cancel') }}</x-ui.button>
        </div>
    </form>
</div>
