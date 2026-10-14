<div class="max-w-3xl">
    <x-admin.page-header :title="__('admin.system.title')" />

    <p class="mb-6">{{ __('admin.system.overview_lead') }}</p>

    <ul class="grid gap-4 sm:grid-cols-2">
        <li>
            <a href="{{ route('admin.system.health') }}" class="block rounded-card border border-grey-250 bg-white p-5 text-charcoal no-underline hover:border-charcoal">
                <span class="block font-semibold">{{ __('admin.system.health_title') }}</span>
            </a>
        </li>
    </ul>
</div>
