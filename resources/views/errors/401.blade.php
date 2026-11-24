<x-layouts::error :title="__('errors.401.title')" :message="__('errors.401.message')" symbol="circle">
    <p class="flex flex-wrap gap-x-6 gap-y-2">
        <a href="{{ route('client.landing') }}" class="font-semibold text-charcoal">{{ __('errors.account') }}</a>
    </p>
</x-layouts::error>
