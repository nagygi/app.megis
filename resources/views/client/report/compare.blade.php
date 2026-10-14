{{-- R1·c · Összevetés: Te · Csapat · Szerv. (27 · 27.9, question 4: a sentence from a template). --}}
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
@endphp
<x-layouts::client :title="__('report.question.compare')" color="green" :subtitle="__('report.frozen', ['date' => \Illuminate\Support\Carbon::parse($report['meta']['published_on'])->format('m.d.')])" :back="$url('internal')" :back-label="__('report.question.internal')">
    <div class="flex max-w-2xl flex-col gap-6">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-grey-250">
                    <th scope="col" class="py-2"><span class="sr-only">{{ __('report.chapters.values') }}</span></th>
                    <th scope="col" class="py-2 text-right">{{ __('report.compare.you') }}</th>
                    <th scope="col" class="py-2 text-right">{{ __('report.compare.team') }}</th>
                    <th scope="col" class="py-2 text-right">{{ __('report.compare.organization') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($keys as $key)
                    @php($you = $mine->get($key)['score'] ?? null)
                    @php($teamScore = $team->get($key)['score'] ?? null)
                    <tr class="border-b border-grey-200">
                        <th scope="row" class="py-2 font-normal">{{ $team->get($key)['label'] }}</th>
                        <td class="py-2 text-right tabular-nums">{{ $whole($you) }}</td>
                        <td @class(['py-2 text-right tabular-nums', 'font-semibold text-coral-ink' => $you !== null && $teamScore !== null && abs($you - $teamScore) >= 2])>{{ $whole($teamScore) }}</td>
                        <td class="py-2 text-right tabular-nums">{{ $whole($org->get($key)['score'] ?? null) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p>
            {{ __('report.compare.sentence', [
                'you' => \App\Services\Report\ReportText::decimal($mean($mine->only($keys->all()))),
                'team' => \App\Services\Report\ReportText::decimal($mean($team->only($keys->all()))),
                'organization' => \App\Services\Report\ReportText::decimal($mean($org->only($keys->all()))),
            ]) }}
            @if ($largest)
                {{ __('report.compare.biggest', ['label' => $team->get($largest['key'])['label'], 'you' => $whole($largest['you']), 'team' => $whole($largest['team'])]) }}
            @endif
        </p>

        <a href="{{ $url('internal') }}" class="self-start font-semibold">{{ __('report.back_to_chapter', ['chapter' => __('report.question.internal')]) }}</a>
    </div>
</x-layouts::client>
