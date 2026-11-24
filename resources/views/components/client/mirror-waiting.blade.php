{{-- An invited member's mirror is done: nothing more to do until the diagnosis starts (06 · 6.2, 26 · 26.4). --}}
<div {{ $attributes->class(['on-dark flex items-center gap-4 rounded-card bg-charcoal p-5 text-cream']) }} role="status">
    <x-megis.symbol name="growth-rings" class="size-12 shrink-0 text-yellow" />
    <div class="flex min-w-0 flex-col gap-1">
        <p class="text-xl leading-tight font-extrabold text-yellow uppercase">{{ __('mirror.waiting_title') }}</p>
        <p>{{ __('mirror.waiting') }}</p>
        {{ $slot }}
    </div>
</div>
