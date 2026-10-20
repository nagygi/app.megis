{{-- BT1 · Bölcsességtár (32 · 32.1). --}}
<x-layouts::client :title="__('wisdom.title')" color="cream" :subtitle="__('wisdom.subtitle')" :back="route('client.home', $organization)" :back-label="__('client.menu.home')">
    <div class="flex max-w-2xl flex-col gap-6">
        <h2 class="text-xl font-semibold">{{ __('wisdom.question') }}</h2>
        <ul class="flex flex-col gap-2">
            @foreach ($topics as $topic)
                <li>
                    <x-ui.link-row :href="route('client.wisdom.show', [$organization, $topic['slug']])" :title="$topic['title']" :meta="$topic['sentence']" />
                </li>
            @endforeach
        </ul>
    </div>
</x-layouts::client>
