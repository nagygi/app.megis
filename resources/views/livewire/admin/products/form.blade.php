<div class="max-w-2xl">
    <x-admin.page-header :title="$title" />

    <form wire:submit="save" class="flex flex-col gap-5 rounded-card border border-grey-250 bg-white p-6">
        <x-form.input name="name" wire:model="name" :label="__('admin.products.name')" required />
        <x-form.textarea name="description" wire:model="description" :label="__('admin.products.description')" />

        <div class="flex flex-wrap gap-3">
            <x-ui.button type="submit">{{ __('admin.common.save') }}</x-ui.button>
            <x-ui.button tone="outline" :href="route('admin.products.index')">{{ __('admin.common.cancel') }}</x-ui.button>
        </div>
    </form>
</div>
