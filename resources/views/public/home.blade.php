{{-- ET1 · the opening of the Értéktükör, the public home (06 · 6.1). --}}
<x-layouts::public tone="coral" :title="__('mirror.meta_title')" :description="__('mirror.meta_description')">
    <section class="relative flex flex-col gap-8 py-10 sm:py-16">
        <x-megis.symbol name="fire" class="pointer-events-none absolute -right-16 -bottom-10 size-80 text-coral-dark sm:size-[28rem]" />

        <h1 class="relative text-5xl leading-[0.95] font-extrabold uppercase sm:text-7xl">
            <span class="block text-black">{{ __('mirror.manifesto.line1') }}</span>
            <span class="block text-black">{{ __('mirror.manifesto.line2') }}</span>
            <span class="block text-cream">{{ __('mirror.manifesto.line3') }}</span>
        </h1>

        <div class="relative flex max-w-prose flex-col gap-3 text-xl text-black">
            <p>{{ __('mirror.manifesto.body') }}</p>
            <p class="font-semibold">{{ __('mirror.manifesto.closing') }}</p>
        </div>

        <div class="relative flex flex-wrap gap-3">
            <x-ui.button size="lg" icon-right="arrow-right" :href="route('mirror.record')">{{ __('mirror.start') }}</x-ui.button>
            <x-ui.button size="lg" tone="cream" :href="route('client.login')">{{ __('mirror.have_account') }}</x-ui.button>
        </div>
    </section>

    <x-slot:footer>
        <x-public.legal-links />
    </x-slot:footer>
</x-layouts::public>
