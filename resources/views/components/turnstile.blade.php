{{--
    Cloudflare Turnstile widget (NFR · 5).
    In a plain form Cloudflare adds the hidden "cf-turnstile-response" field.
    With "model" (a Livewire property) the widget is rendered explicitly and
    hands its token to that property, also after Livewire re-renders the page.
--}}
@props(['model' => null])

<div {{ $attributes->class(['flex flex-col gap-1.5']) }}>
    @if ($model)
        <div
            wire:ignore
            x-data
            x-init="
                const render = () => window.turnstile
                    ? turnstile.render($refs.widget, {
                        sitekey: @js(config('services.turnstile.site_key')),
                        language: 'hu',
                        theme: 'light',
                        callback: (token) => $wire.set(@js($model), token, false),
                        'expired-callback': () => $wire.set(@js($model), '', false),
                    })
                    : setTimeout(render, 200);
                render();
            "
        >
            <div x-ref="widget"></div>
        </div>
        <x-form.error :name="$model" :id="$model" />
    @else
        <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-language="hu" data-theme="light"></div>
        <x-form.error name="cf-turnstile-response" id="cf-turnstile-response" />
    @endif
</div>

@pushOnce('head')
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endPushOnce
