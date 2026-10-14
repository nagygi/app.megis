{{-- Organization home (charcoal header, 15 · 15.2). The diagnosis tickets replace the content. --}}
<x-layouts::client :title="__('client.home.title')" color="charcoal" :subtitle="$organization->name">
    <section class="flex flex-col gap-4">
        <x-megis.symbol name="tree-of-life" class="size-14 text-charcoal" />
        <h2 class="text-xl font-semibold">{{ __('client.home.greeting', ['name' => $user->first_name ?? $user->name]) }}</h2>
        <p>{{ __('client.home.lead', ['organization' => $organization->name]) }}</p>
    </section>
</x-layouts::client>
