{{--
    ET5 · the nine elements of the tree on a circle around the user's values
    (06 · 6.1). Clockwise from the top; it scales with the width of the phone.
    With a center line instead (an invited member's first mirror, 06 · 6.2),
    the circle stands around that line, not the values.
--}}
@php
    $elements = ['root', 'trunk', 'fruit', 'shade', 'soil', 'mycelium', 'growth-rings', 'circle', 'scent'];
    $center ??= null;
    $label = $center === null
        ? __('mirror.circle_label', ['values' => $names->implode(', ')])
        : __('mirror.circle_label_plain', ['center' => $center, 'elements' => collect($elements)->map(fn ($element) => __('mirror.elements.'.$element))->implode(', ')]);
@endphp

<figure class="relative mx-auto aspect-square w-full max-w-80" aria-label="{{ $label }}">
    <div class="absolute inset-[11%] rounded-full border-[1.5px] border-dashed border-grey-400" aria-hidden="true"></div>

    <div class="absolute inset-[26%] flex flex-col items-center justify-center gap-1 rounded-full bg-yellow p-3 text-center">
        @if ($center !== null)
            <span class="text-sm leading-snug font-semibold [text-wrap:balance]">{{ $center }}</span>
        @else
            <span class="text-xs font-semibold tracking-wide uppercase">{{ __('mirror.your_values') }}</span>
            @foreach ($names as $name)
                <span class="text-sm leading-snug font-semibold">{{ $name }}</span>
            @endforeach
        @endif
    </div>

    <ul aria-hidden="true">
        @foreach ($elements as $index => $element)
            @php
                $angle = deg2rad(-90 + $index * 40);
            @endphp
            <li
                class="absolute flex w-1/5 -translate-x-1/2 -translate-y-1/2 flex-col items-center gap-1"
                style="left: {{ round(50 + 40 * cos($angle), 2) }}%; top: {{ round(50 + 40 * sin($angle), 2) }}%"
            >
                <x-megis.symbol :name="$element" class="size-7" />
                <span class="text-xs leading-none whitespace-nowrap">{{ __('mirror.elements.'.$element) }}</span>
            </li>
        @endforeach
    </ul>
</figure>
