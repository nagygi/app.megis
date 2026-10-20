{{-- A topic of the Bölcsességtár (32 · 32.2, 32.3). --}}
@php($markdown = fn (string $text) => \Illuminate\Support\Str::markdown($text, ['html_input' => 'escape', 'allow_unsafe_links' => false]))
<x-layouts::client :title="$topic['title']" color="cream" :subtitle="__('wisdom.subtitle')" :back="route('client.wisdom.index', $organization)" :back-label="__('wisdom.title')">
    <div class="flex max-w-2xl flex-col gap-6">
        @if (filled($topic['intro']))
            <div class="prose-megis text-grey-700">{!! $markdown($topic['intro']) !!}</div>
        @endif

        <ul class="flex flex-col gap-2">
            @foreach ($topic['items'] as $item)
                <x-ui.card as="li" padding="none">
                    <details class="group">
                        <summary class="flex min-h-11 cursor-pointer list-none items-center gap-3 rounded-card px-5 py-4 hover:bg-paper [&::-webkit-details-marker]:hidden">
                            <x-megis.symbol :name="$item['symbol']" class="size-8 shrink-0" />
                            <span class="flex-1">
                                <span class="block font-semibold">{{ $item['name'] }}</span>
                                <span class="block text-sm text-grey-700">{{ $item['sentence'] }}</span>
                            </span>
                            <x-megis.icon name="chevron-down" class="shrink-0 transition-transform duration-200 group-open:rotate-180" />
                        </summary>
                        <div class="flex flex-col items-start gap-2 px-5 pb-4">
                            @if (filled($item['description']))
                                <div class="prose-megis text-sm">{!! $markdown($item['description']) !!}</div>
                            @endif
                            @if ($chapterUrl)
                                <x-ui.button tone="ghost" size="sm" icon-right="chevron-right" class="-ml-1.5" :href="$chapterUrl">{{ __('wisdom.where') }}</x-ui.button>
                            @endif
                        </div>
                    </details>
                    @if (isset($ours[$item['symbol']]))
                        <p class="px-5 pb-4 text-sm"><span class="font-semibold">{{ __('wisdom.ours') }}</span> {{ $ours[$item['symbol']] }}</p>
                    @endif
                </x-ui.card>
            @endforeach

            @foreach ($areas as $area)
                <x-ui.card as="li" padding="none">
                    <details class="group">
                        <summary class="flex min-h-11 cursor-pointer list-none items-center gap-3 rounded-card px-5 py-4 hover:bg-paper [&::-webkit-details-marker]:hidden">
                            <span class="flex-1">
                                <span class="block font-semibold">{{ $area->name }}</span>
                                @if ($area->what_we_look_at)
                                    <span class="block text-sm text-grey-700">{{ $area->what_we_look_at }}</span>
                                @endif
                            </span>
                            <x-megis.icon name="chevron-down" class="shrink-0 transition-transform duration-200 group-open:rotate-180" />
                        </summary>
                        <div class="flex flex-col items-start gap-1 px-5 pb-4 text-sm">
                            @if ($area->trace)
                                <p><span class="font-semibold">{{ __('wisdom.trace') }}</span> {{ $area->trace }}</p>
                            @endif
                            @if ($area->six_description)
                                <p><span class="font-semibold">{{ __('wisdom.six') }}</span> {{ $area->six_description }}</p>
                            @endif
                            @if ($chapterUrl)
                                <x-ui.button tone="ghost" size="sm" icon-right="chevron-right" class="-ml-1.5" :href="$chapterUrl">{{ __('wisdom.where') }}</x-ui.button>
                            @endif
                        </div>
                    </details>
                </x-ui.card>
            @endforeach
        </ul>

        @if ($topic['slug'] === 'szazalek-es-pontok' && $report && isset($report->calculation['ring']))
            <p class="on-dark rounded-card bg-green px-5 py-4 text-cream">{{ __('wisdom.your_percent', ['percent' => $report->calculation['ring'], 'date' => $report->published_at?->format('Y.m.d.')]) }}</p>
        @endif

        <x-ui.page-nav
            :label="__('wisdom.title')"
            :position="__('wisdom.stepper', ['current' => $position, 'total' => count(\App\Services\Wisdom\WisdomRepository::TOPICS), 'topic' => $topic['title']])"
            :next="$next ? route('client.wisdom.show', [$organization, $next['slug']]) : null"
            :next-label="$next ? __('wisdom.next', ['topic' => $next['title']]) : null"
        />
    </div>
</x-layouts::client>
