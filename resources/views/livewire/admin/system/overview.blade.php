<div class="max-w-3xl">
    <x-admin.page-header :title="__('admin.system.title')" :description="__('admin.system.overview_lead')" />

    <ul class="grid gap-4 sm:grid-cols-2">
        <li>
            <x-ui.link-row :href="route('admin.system.health')" class="border border-grey-250">{{ __('admin.system.health_title') }}</x-ui.link-row>
        </li>
    </ul>
</div>
