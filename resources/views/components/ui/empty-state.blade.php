{{--
    What a list shows when it has nothing yet: a symbol, a sentence and,
    when there is one, the action that fills it ("Még nincs folyamat" +
    "Hozzáadom"). A filter with no hits says so in its own words instead.
--}}
@props(['symbol' => null, 'title' => null])

<div {{ $attributes->class(['flex flex-col items-start gap-3 rounded-card bg-white p-6 text-charcoal']) }}>
    @if ($symbol)
        <x-megis.symbol :name="$symbol" class="size-10" />
    @endif
    @if ($title)
        <p class="font-semibold">{{ $title }}</p>
    @endif
    @if ((string) $slot !== '')
        <div class="text-grey-700">{{ $slot }}</div>
    @endif
    @isset($action)
        <div class="flex flex-wrap gap-2">{{ $action }}</div>
    @endisset
</div>
