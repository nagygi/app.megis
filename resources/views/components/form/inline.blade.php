{{--
    A field and its button on one line ("Mentem" next to a date, "Hozzáadom"
    next to a name). The buttons line up with the field, not with its label,
    and stay put when a help or error line appears under the field; on a
    phone they wrap under it. Fields in the slot, buttons in "actions".
--}}
<div {{ $attributes->class(['flex flex-wrap items-start gap-x-3 gap-y-2']) }}>
    <div class="min-w-0 flex-1 basis-60">{{ $slot }}</div>
    {{-- The label line: text-sm (20px) + the field's gap (6px). --}}
    <div class="flex flex-wrap gap-2 pt-[1.625rem]">{{ $actions }}</div>
</div>
