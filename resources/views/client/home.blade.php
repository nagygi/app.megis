{{-- Organization home (charcoal header, 15 · 15.2): the user's diagnoses. --}}
<x-layouts::client :title="__('client.home.title')" color="charcoal" :subtitle="$organization->name">
    <section class="flex max-w-2xl flex-col gap-4">
        <x-megis.symbol name="tree-of-life" class="size-14 text-charcoal" />
        <h2 class="text-xl font-semibold">{{ __('client.home.greeting', ['name' => $user->first_name ?? $user->name]) }}</h2>

        @if ($diagnoses->isEmpty())
            <p>{{ $lead }}</p>
        @else
            <ul class="flex flex-col gap-2" aria-label="{{ __('client.home.diagnoses') }}">
                @foreach ($diagnoses as $main)
                    <li>
                        <x-ui.link-row
                            :href="route('client.diagnosis.show', [$organization, $main->diagnosis])"
                            :title="$main->subjectName().' · '.$main->product_name"
                            :meta="collect([
                                $main->isLeader($user) ? __('enums.participant_role.leader') : __('enums.participant_role.peer'),
                                $main->stage->label(),
                                __('client.home.publishes', ['date' => $main->publishes_on->format('Y.m.d.')]),
                            ])->implode(' · ')"
                        />
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-layouts::client>
