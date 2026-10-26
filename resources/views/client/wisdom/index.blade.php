{{-- BT1 · Bölcsességtár (32 · 32.1): the topics in rows divided by a line, each with its symbol. --}}
<x-layouts::client
    :title="__('wisdom.title')"
    color="cream-line"
    symbol="growth-rings"
    :label="__('wisdom.title')"
    :subtitle="__('wisdom.question')"
    :note="__('wisdom.subtitle')"
    :back="route('client.home', $organization)"
    :back-label="__('client.menu.home')"
>
    <ul class="flex max-w-2xl flex-col divide-y divide-grey-200">
        @foreach ($topics as $topic)
            <li>
                <x-ui.list-row :href="route('client.wisdom.show', [$organization, $topic['slug']])" :title="$topic['title']" :meta="$topic['sentence']" :symbol="$topic['symbol']" />
            </li>
        @endforeach
    </ul>
</x-layouts::client>
