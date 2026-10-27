{{-- V6·d · Detektált folyamatok (11 · 11.2). --}}
<div class="flex max-w-2xl flex-col gap-6" @if ($dictations !== []) wire:poll.2s="pollDictations" @endif>
    <x-ui.flash />

    <div class="flex flex-col gap-1">
        @if ($connection)
            <p class="text-grey-700">{{ __('processes.list.events', ['count' => $connection->event_count]) }}</p>
        @endif
        @if ($room !== null)
            <p class="text-sm text-grey-700">{{ __('processes.list.room_left', ['count' => $room]) }}</p>
        @endif
    </div>

    <x-form.error name="accept" id="accept" />

    @foreach (['strong', 'uncertain', 'manual'] as $group)
        @if ($groups[$group]->isNotEmpty())
            <section class="flex flex-col gap-3" aria-labelledby="group-{{ $group }}">
                <h3 id="group-{{ $group }}" class="text-lg font-semibold">{{ __('processes.list.groups.'.$group) }} · {{ $groups[$group]->count() }}</h3>
                <ul class="flex flex-col gap-3">
                    @foreach ($groups[$group] as $process)
                        @include('livewire.client.diagnosis.partials.process-card', ['process' => $process])
                    @endforeach
                </ul>
            </section>
        @endif
    @endforeach

    @php($otherEvents = ($connection?->one_off_count ?? 0) + ($connection?->unlinked_count ?? 0))
    @if ($groups['other']->isNotEmpty() || $otherEvents > 0)
        <section class="flex flex-col gap-3" aria-labelledby="group-other">
            <h3 id="group-other" class="text-lg font-semibold">{{ __('processes.list.groups.other') }}</h3>
            @if ($connection)
                <p class="text-sm">{{ __('processes.list.other_events', ['count' => $connection->one_off_count]) }} · {{ __('processes.list.unlinked', ['count' => $connection->unlinked_count]) }}</p>
            @endif
            <ul class="flex flex-col gap-3">
                @foreach ($groups['other'] as $process)
                    @include('livewire.client.diagnosis.partials.process-card', ['process' => $process])
                @endforeach
            </ul>
            <p class="text-sm text-grey-700">{{ __('processes.list.other_help') }}</p>
        </section>
    @endif

    @if (collect($groups)->flatten()->isEmpty())
        <x-ui.empty-state symbol="spiral">{{ __('processes.list.empty') }}</x-ui.empty-state>
    @endif

    @if ($editable)
        <x-ui.card as="form" wire:submit="addManual" class="flex flex-col gap-4">
            <h3 class="text-lg font-semibold">{{ __('processes.list.add_title') }}</h3>
            <x-form.input name="name" wire:model="name" :label="__('processes.list.fields.name')" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="rhythm" wire:model="rhythm" :label="__('processes.list.fields.rhythm')">
                    <option value="">–</option>
                    @foreach (\App\Enums\ProcessRhythm::cases() as $rhythmOption)
                        <option value="{{ $rhythmOption->value }}">{{ $rhythmOption->label() }}</option>
                    @endforeach
                </x-form.select>
                <x-form.select name="parentId" wire:model="parentId" :label="__('processes.list.fields.parent')">
                    <option value="">{{ __('processes.list.parent_none') }}</option>
                    @foreach ($parents as $parent)
                        <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                    @endforeach
                </x-form.select>
            </div>
            <x-form.long-text
                name="description"
                wire:model="description"
                :label="__('processes.sheet.description')"
                rows="3"
                :max="\App\Livewire\Client\ProcessSheet::DESCRIPTION_MAX"
                :pending="isset($dictations['description'])"
                :tools="$canDictate"
            />
            <x-ui.button type="submit" tone="outline" icon="plus" class="self-start" data-focus-errors>{{ __('processes.list.add') }}</x-ui.button>
        </x-ui.card>

        <x-processes.import :rows="$importRows" :parents="$parents" :can-dictate="$canDictate" :pending="isset($dictations['importText'])" />
    @endif

    @if ($goingOn->isNotEmpty())
        <x-ui.card as="section" tone="cream" bordered class="flex flex-col gap-2" aria-labelledby="going-on">
            <h3 id="going-on" class="font-semibold">{{ __('processes.list.going_on') }}</h3>
            <ul class="flex flex-col gap-1 text-sm">
                @foreach ($goingOn as $process)
                    <li>
                        <span class="font-semibold">{{ $process->name }}</span>
                        · {{ $process->kind->label() }}
                        @if ($process->children_count > 0) · {{ __('processes.list.subs', ['count' => $process->children_count]) }} @endif
                        @if (! $process->isInCalendar()) · {{ $process->confidence->label() }} · {{ __('processes.library.not_in_calendar') }} @endif
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif

    @if ($editable)
        <div class="flex flex-wrap items-center gap-3">
            {{-- Accepting is the way on while something waits for it (prototype V6·d). --}}
            @if ($unaccepted > 0)
                <x-ui.button size="lg" icon-right="arrow-right" wire:click="acceptAll">{{ trans_choice('processes.list.accept_all', $unaccepted, ['count' => $unaccepted]) }}</x-ui.button>
            @else
                <x-ui.button size="lg" icon-right="arrow-right" :href="route('client.diagnosis.process-summary', [$organization, $diagnosis])">{{ __('diagnosis.next') }}</x-ui.button>
            @endif
            <x-ui.button tone="ghost" :href="route('client.diagnosis.folyamatok', [$organization, $diagnosis])">{{ __('processes.list.later') }}</x-ui.button>
        </div>
    @endif
</div>
