{{-- BT1 · Bölcsességtár (32 · 32.1). --}}
<x-layouts::client :title="__('wisdom.title')" color="cream" :subtitle="__('wisdom.subtitle')" :back="route('client.home', $organization)" :back-label="__('client.menu.home')">
    <div class="flex max-w-2xl flex-col gap-6">
        <h2 class="text-2xl font-semibold">{{ __('wisdom.question') }}</h2>
        <ul class="flex flex-col gap-2">
            @foreach ($topics as $topic)
                <li>
                    <a href="{{ route('client.wisdom.show', [$organization, $topic['slug']]) }}" class="flex items-center justify-between gap-3 rounded-card bg-white px-5 py-4 text-charcoal no-underline hover:bg-paper">
                        <span>
                            <span class="block font-semibold">{{ $topic['title'] }}</span>
                            <span class="block text-sm text-grey-700">{{ $topic['sentence'] }}</span>
                        </span>
                        <x-megis.icon name="chevron-right" class="size-4 shrink-0" />
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</x-layouts::client>
