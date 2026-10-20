{{--
    A note inside the page, as opposed to the toasts of an action:
    - info: something to know (yellow-pale), no live region;
    - success: done (green-soft), read out politely;
    - warning: needs a look (yellow-light);
    - error: something failed or is missing (coral-light), read out at once.
    An optional title, and an "action" slot on the right (a link or a button).
--}}
@props(['tone' => 'info', 'title' => null])

@php
    $tones = [
        'info' => 'bg-yellow-pale text-charcoal',
        'success' => 'bg-green-soft text-green',
        'warning' => 'bg-yellow-light text-charcoal',
        'error' => 'bg-coral-light text-black',
    ];
    $role = ['success' => 'status', 'error' => 'alert'][$tone] ?? null;
@endphp

<div @if ($role) role="{{ $role }}" @endif {{ $attributes->class(['flex flex-wrap items-start gap-x-4 gap-y-2 rounded-field px-4 py-3', $tones[$tone]]) }}>
    @if ($tone === 'success')
        <x-megis.icon name="check" class="mt-1" />
    @endif
    <div class="flex min-w-0 flex-1 flex-col gap-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div>{{ $slot }}</div>
    </div>
    @isset($action)
        <div class="shrink-0">{{ $action }}</div>
    @endisset
</div>
