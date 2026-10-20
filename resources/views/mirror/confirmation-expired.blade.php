<x-layouts::client :title="__('mirror.confirm_title')" guest>
    <div class="flex max-w-md flex-col gap-4">
        <p>{{ __('mirror.confirm_expired') }}</p>
        <x-ui.button size="lg" icon-right="arrow-right" :href="route('mirror.record')" class="self-start">{{ __('mirror.start') }}</x-ui.button>
    </div>
</x-layouts::client>
