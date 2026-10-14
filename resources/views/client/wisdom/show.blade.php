{{-- A topic of the Bölcsességtár (32 · 32.2, 32.3). --}}
@php($markdown = fn (string $text) => \Illuminate\Support\Str::markdown($text, ['html_input' => 'escape', 'allow_unsafe_links' => false]))
<x-layouts::client :title="$topic['title']" color="cream" :subtitle="__('wisdom.subtitle')" :back="route('client.wisdom.index', $organization)" :back-label="__('wisdom.title')">
    <div class="flex max-w-2xl flex-col gap-6">
        @if (filled($topic['intro']))
            <div class="text-grey-700">{!! $markdown($topic['intro']) !!}</div>
        @endif

        <ul class="flex flex-col gap-2">
            @foreach ($topic['items'] as $item)
                <li class="rounded-card bg-white px-5 py-4">
                    <details>
                        <summary class="flex cursor-pointer items-center gap-3">
                            <x-megis.symbol :name="$item['symbol']" class="size-8 shrink-0" />
                            <span>
                                <span class="block font-semibold">{{ $item['name'] }}</span>
                                <span class="block text-sm text-grey-700">{{ $item['sentence'] }}</span>
                            </span>
                        </summary>
                        @if (filled($item['description']))
                            <div class="mt-3 text-sm">{!! $markdown($item['description']) !!}</div>
                        @endif
                        @if ($chapterUrl)
                            <a href="{{ $chapterUrl }}" class="mt-2 inline-block text-sm font-semibold">{{ __('wisdom.where') }}</a>
                        @endif
                    </details>
                    @if (isset($ours[$item['symbol']]))
                        <p class="mt-2 text-sm"><span class="font-semibold">{{ __('wisdom.ours') }}</span> {{ $ours[$item['symbol']] }}</p>
                    @endif
                </li>
            @endforeach

            @foreach ($areas as $area)
                <li class="rounded-card bg-white px-5 py-4">
                    <details>
                        <summary class="cursor-pointer">
                            <span class="block font-semibold">{{ $area->name }}</span>
                            @if ($area->what_we_look_at)
                                <span class="block text-sm text-grey-700">{{ $area->what_we_look_at }}</span>
                            @endif
                        </summary>
                        @if ($area->trace)
                            <p class="mt-3 text-sm"><span class="font-semibold">{{ __('wisdom.trace') }}</span> {{ $area->trace }}</p>
                        @endif
                        @if ($area->six_description)
                            <p class="mt-1 text-sm"><span class="font-semibold">{{ __('wisdom.six') }}</span> {{ $area->six_description }}</p>
                        @endif
                        @if ($chapterUrl)
                            <a href="{{ $chapterUrl }}" class="mt-2 inline-block text-sm font-semibold">{{ __('wisdom.where') }}</a>
                        @endif
                    </details>
                </li>
            @endforeach
        </ul>

        @if ($topic['slug'] === 'szazalek-es-pontok' && $report && isset($report->calculation['ring']))
            <p class="rounded-card bg-green px-5 py-4 text-cream on-dark">{{ __('wisdom.your_percent', ['percent' => $report->calculation['ring'], 'date' => $report->published_at?->format('Y.m.d.')]) }}</p>
        @endif

        <nav class="flex flex-wrap items-center justify-between gap-3 border-t border-grey-250 pt-4 text-sm" aria-label="{{ __('wisdom.title') }}">
            <span class="text-grey-700">{{ __('wisdom.stepper', ['current' => $position, 'total' => count(\App\Services\Wisdom\WisdomRepository::TOPICS), 'topic' => $topic['title']]) }}</span>
            @if ($next)
                <a href="{{ route('client.wisdom.show', [$organization, $next['slug']]) }}" class="font-semibold">{{ __('wisdom.next', ['topic' => $next['title']]) }}</a>
            @endif
        </nav>
    </div>
</x-layouts::client>
