{{-- The admin's preview of V12, V12·b (21 · 21.1): the leader's home, from the live data, in its own view. --}}
<x-layouts::client
    :title="__('diagnosis.home.title')"
    :label="__('client.header.diagnosis')"
    color="charcoal"
    :subtitle="__('diagnosis.published_home.subtitle', ['product' => $main->product_name, 'date' => \Illuminate\Support\Carbon::parse($report['meta']['published_on'])->format('m.d.')])"
>
    @include('livewire.client.diagnosis.overview-published')
</x-layouts::client>
