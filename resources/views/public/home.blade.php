{{--
    Temporary public home with the legal links, published early because the
    Google verification of the calendar app needs it (09). The Értéktükör (06 · ET1) replaces it.
--}}
<x-layouts::public>
    <section class="flex flex-col gap-6 py-12 sm:py-20">
        <x-megis.symbol name="tree-of-life" class="size-20 text-charcoal" />
        <h1 class="text-5xl leading-none font-extrabold uppercase sm:text-7xl">{{ __('public.home.headline') }}</h1>
        <p class="max-w-prose text-lg">{{ __('public.home.lead') }}</p>
        <x-ui.button :href="route('client.login')" class="self-start">{{ __('public.home.login') }}</x-ui.button>
    </section>

    <x-slot:footer>
        <x-public.legal-links />
    </x-slot:footer>
</x-layouts::public>
