{{--
    R5 · the tree on a circle (27 · 27.8, prototype R5): the nine elements
    clockwise from the top, the inside PHI in green; the outside side and the
    five further elements (NM-14) are coming, so they stay dashed. A coral
    line joins two measured elements two steps apart or more (a crack).
--}}
@props(['rows' => [], 'average' => null])

@php
    $order = ['root', 'trunk', 'fruit', 'shade', 'soil', 'mycelium', 'growth-rings', 'circle', 'scent'];
    $scores = collect($rows)->mapWithKeys(fn (array $row) => [\App\Enums\TreeElement::from($row['element'])->symbol() => $row]);
    $nodes = collect($order)->map(function (string $symbol, int $index) use ($scores) {
        $angle = deg2rad(-90 + $index * 40);
        $row = $scores->get($symbol);

        return [
            'symbol' => $symbol,
            'x' => round(150 + 100 * cos($angle), 1),
            'y' => round(150 + 100 * sin($angle), 1),
            'top' => sin($angle) < 0.2,
            'row' => $row,
            'whole' => $row !== null && $row['score_state'] === 'scored' ? $row['score_whole'] : null,
        ];
    });
    $measured = $nodes->filter(fn (array $node) => $node['whole'] !== null)->values();
    $cracks = $measured->flatMap(fn (array $a, int $i) => $measured->slice($i + 1)->filter(fn (array $b) => abs($a['whole'] - $b['whole']) >= 2)->map(fn (array $b) => [$a, $b]))->values();
@endphp

<figure {{ $attributes->class(['flex flex-col gap-2 rounded-[24px] border border-grey-200 bg-cream px-2 pt-2.5 pb-3']) }}>
    <svg viewBox="0 0 300 310" class="block w-full" role="img" aria-label="{{ __('report.tree.circle_label', ['elements' => $measured->map(fn (array $node) => __('mirror.elements.'.$node['symbol']).' '.$node['whole'])->implode(', ')]) }}">
        @foreach ($cracks as [$a, $b])
            <line x1="{{ $a['x'] }}" y1="{{ $a['y'] }}" x2="{{ $b['x'] }}" y2="{{ $b['y'] }}" stroke-width="3" stroke-linecap="round" class="stroke-coral" />
        @endforeach
        <text x="150" y="146" text-anchor="middle" class="fill-grey-700 text-[11px] font-semibold">{{ __('report.tree.center') }}</text>
        <text x="150" y="170" text-anchor="middle" class="fill-charcoal text-[22px] font-extrabold">{{ \App\Services\Report\ReportText::decimal($average) }}</text>
        @foreach ($nodes as $node)
            @if ($node['whole'] !== null)
                <circle cx="{{ $node['x'] }}" cy="{{ $node['y'] }}" r="17" class="fill-green" />
                <text x="{{ $node['x'] }}" y="{{ $node['y'] + 4.5 }}" text-anchor="middle" class="fill-cream text-[13px] font-extrabold">{{ $node['whole'] }}</text>
            @else
                <circle cx="{{ $node['x'] }}" cy="{{ $node['y'] }}" r="16" fill="none" stroke-width="1.5" stroke-dasharray="3 3" class="stroke-grey-500" />
                <text x="{{ $node['x'] }}" y="{{ $node['y'] + 4.5 }}" text-anchor="middle" class="fill-grey-700 text-[13px] font-semibold">–</text>
            @endif
            <text x="{{ $node['x'] }}" y="{{ $node['top'] ? $node['y'] - 24 : $node['y'] + 33 }}" text-anchor="middle" @class(['text-[10px] font-semibold', 'fill-charcoal' => $node['row'] !== null, 'fill-grey-700' => $node['row'] === null])>{{ __('mirror.elements.'.$node['symbol']) }}</text>
        @endforeach
    </svg>
    <figcaption class="flex flex-wrap items-center gap-x-3 gap-y-1 px-2 text-xs text-grey-700">
        <span class="flex items-center gap-1"><span class="size-2.5 rounded-pill bg-green" aria-hidden="true"></span>{{ mb_strtolower(__('report.tree.inside')) }}</span>
        <span class="flex items-center gap-1"><span class="size-2.5 rounded-pill border border-dashed border-grey-600" aria-hidden="true"></span>{{ mb_strtolower(__('report.tree.outside')) }} · {{ __('report.soon') }}</span>
        <span class="flex items-center gap-1"><span class="h-[3px] w-3.5 bg-coral" aria-hidden="true"></span>{{ __('report.tree.crack') }}</span>
    </figcaption>
</figure>
