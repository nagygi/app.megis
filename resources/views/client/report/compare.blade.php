{{-- R1·c · Te · csapat · szervezet (27 · 27.9, prototype R1·c): one table per chapter, coral where the team is two steps from you; the sentence from a template (question 4). --}}
@use('App\Services\Report\ReportService')
@use('App\Services\Report\ReportText')
@php
    $team = collect($report['views']['team'])->keyBy('key');
    $mine = $individual->keyBy('key');
    $org = $organizationView->keyBy('key');
    $keys = $team->filter(fn ($row) => in_array($row['kind'], ['value', 'process', 'area'], true))->keys();
    $mean = fn ($rows) => \App\Services\Diagnoses\ScoreMath::round1(\App\Services\Diagnoses\ScoreMath::mean($rows->pluck('score')->filter(fn ($score) => $score !== null)->values()->all()));
    $largest = $keys->map(fn ($key) => ['key' => $key, 'you' => $mine->get($key)['score'] ?? null, 'team' => $team->get($key)['score'] ?? null])
        ->filter(fn ($pair) => $pair['you'] !== null && $pair['team'] !== null && abs($pair['you'] - $pair['team']) >= 2)
        ->sortByDesc(fn ($pair) => abs($pair['you'] - $pair['team']))
        ->first();
    $whole = fn ($score) => $score === null ? '–' : (int) floor($score + 0.5);
    $chapters = collect(['values' => 'value', 'processes' => 'process', 'areas' => 'area'])
        ->map(fn (string $kind) => $keys->filter(fn ($key) => $team->get($key)['kind'] === $kind)->values())
        ->filter(fn ($chapterKeys) => $chapterKeys->isNotEmpty());
@endphp
<x-layouts::client
    :title="__('report.pages.compare')"
    color="green"
    symbol="mycelium"
    :label="__('report.pages.compare')"
    :subtitle="__('report.compare_page.question')"
    :note="__('report.compare_page.meta')"
    :back="$url('internal')"
    :back-label="__('report.pages.internal')"
>
    <div class="flex max-w-2xl flex-col gap-4">
        <p class="text-sm leading-relaxed">{{ __('report.compare_page.lead') }}</p>

        @foreach ($chapters as $chapter => $chapterKeys)
            <x-ui.card padding="none" class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-grey-100 text-xs font-semibold tracking-[.05em] text-green uppercase">
                        <tr>
                            <th scope="col" class="px-3 py-2">{{ ReportText::groupTitle($chapter, $chapterKeys->count()) }}</th>
                            <th scope="col" class="w-12 px-1 py-2 text-center">{{ __('report.compare.you') }}</th>
                            <th scope="col" class="w-14 px-1 py-2 text-center"><span class="rounded-field bg-yellow px-1.5 py-0.5 text-charcoal">{{ __('report.compare.team') }}</span></th>
                            <th scope="col" class="w-14 px-1 py-2 text-center">{{ __('report.compare.organization') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($chapterKeys as $key)
                            @php
                                $you = $mine->get($key)['score'] ?? null;
                                $teamScore = $team->get($key)['score'] ?? null;
                            @endphp
                            <tr class="border-t border-grey-150">
                                <th scope="row" class="px-3 py-2.5 font-semibold">
                                    <a href="{{ $url('chapter', [ReportService::SLUGS[$chapter]]) }}" class="text-charcoal no-underline hover:underline">{{ $team->get($key)['label'] }}</a>
                                </th>
                                <td class="px-1 py-2.5 text-center text-grey-700 tabular-nums">{{ $whole($you) }}</td>
                                <td @class(['px-1 py-2.5 text-center font-extrabold tabular-nums', 'text-coral-ink' => $you !== null && $teamScore !== null && abs($you - $teamScore) >= 2])>{{ $whole($teamScore) }}</td>
                                <td class="px-1 py-2.5 text-center text-grey-700 tabular-nums">{{ $whole($org->get($key)['score'] ?? null) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-ui.card>
        @endforeach

        <p class="rounded-[18px] bg-grey-100 px-3.5 py-3 text-sm leading-relaxed">
            {{ __('report.compare.sentence', [
                'you' => ReportText::decimal($mean($mine->only($keys->all()))),
                'team' => ReportText::decimal($mean($team->only($keys->all()))),
                'organization' => ReportText::decimal($mean($org->only($keys->all()))),
            ]) }}
            @if ($largest)
                {{ __('report.compare.biggest', ['label' => $team->get($largest['key'])['label'], 'you' => $whole($largest['you']), 'team' => $whole($largest['team'])]) }}
            @endif
        </p>
        <p class="text-xs leading-relaxed text-grey-700">{{ __('report.compare_page.note') }}</p>

        <x-report.footer :status="$nav['status']" :next="$nav['next']" :next-label="$nav['nextLabel']" />
    </div>
</x-layouts::client>
