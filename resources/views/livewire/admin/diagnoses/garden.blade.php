{{-- A0 · the garden of the main diagnoses, a column per stage (19 · 19.2). --}}
<div class="flex flex-col gap-6">
    <x-ui.flash />

    <x-admin.page-header :title="__('admin.garden.title')" :spaced="false">
        <x-slot:actions>
            <x-ui.button icon="plus" :href="route('admin.main-diagnoses.create')">{{ __('admin.garden.new') }}</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <p class="text-grey-700">
        <a href="{{ route('admin.inbox.index') }}" class="font-semibold">{{ __('admin.garden.today', ['count' => $openCount]) }}</a>
    </p>

    <div class="flex flex-col gap-4">
        <x-ui.segmented :label="__('admin.garden.title')">
            <x-ui.segment selected :href="route('admin.diagnoses.index')">{{ __('admin.garden.view_garden') }}</x-ui.segment>
            <x-ui.segment :href="route('admin.diagnoses.index', ['nezet' => 'lista'])">{{ __('admin.garden.view_list') }}</x-ui.segment>
        </x-ui.segmented>

        {{-- The same build as the list's filters (x-admin.list-toolbar); the checkbox sits on the fields' line. --}}
        <div class="flex flex-wrap items-end gap-3">
            <x-form.select name="subject" wire:model.live="subject" :label="__('admin.garden.subject')">
                <option value="">{{ __('admin.garden.all') }}</option>
                @foreach ($subjects as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-form.select>
            <x-form.select name="contact" wire:model.live="contact" :label="__('admin.garden.contact')">
                <option value="">{{ __('admin.garden.all') }}</option>
                @foreach ($contacts as $admin)
                    <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                @endforeach
            </x-form.select>
            <div class="flex min-h-11 items-center">
                <x-form.checkbox name="showAll" wire:model.live="showAll" :label="__('admin.garden.show_all')" />
            </div>
        </div>
    </div>

    <div class="grid gap-4 overflow-x-auto md:grid-flow-col md:auto-cols-[minmax(14rem,1fr)]">
        @foreach ($columns as $column)
            <x-ui.card as="section" tone="paper" padding="sm" class="flex flex-col gap-3" aria-labelledby="stage-{{ $column['stage']->value }}">
                <h2 id="stage-{{ $column['stage']->value }}" class="flex items-center gap-2 font-semibold">
                    <x-megis.symbol :name="$column['symbol']" class="size-6" />
                    {{ $column['stage']->label() }}
                    <span class="text-sm font-normal text-grey-700">{{ $column['cards']->count() }}</span>
                </h2>
                @forelse ($column['cards'] as $card)
                    @php($main = $card['main'])
                    <x-ui.card
                        as="a"
                        padding="sm"
                        :bordered="! $card['attention']"
                        wire:key="card-{{ $main->id }}"
                        href="{{ route('admin.main-diagnoses.open', $main->diagnosis) }}"
                        @class([
                            'flex flex-col gap-1 text-charcoal no-underline transition-[translate] duration-200 ease-standard hover:-translate-y-0.75',
                            'border-2 border-coral' => $card['attention'],
                        ])
                    >
                        <span class="flex items-start gap-2">
                            <x-megis.symbol :name="$main->subject === \App\Enums\MainDiagnosisSubject::Team ? 'mycelium' : 'circle'" :label="$main->subject->label()" :class="$card['fresh'] ? 'size-5 shrink-0 motion-safe:animate-[pulse_1.2s_ease-in-out_1]' : 'size-5 shrink-0'" />
                            <span class="font-semibold">{{ $main->diagnosis->name }}</span>
                        </span>
                        <span class="text-sm text-grey-700">{{ $main->diagnosis->organization->name }} · {{ $main->product_name }}</span>
                        @if ($card['count'])
                            <span class="text-sm font-semibold">{{ $card['count'] }}</span>
                        @endif
                        @if ($card['attention'])
                            <span class="sr-only">{{ __('admin.garden.attention') }}</span>
                        @endif
                    </x-ui.card>
                @empty
                    <p class="text-sm text-grey-700">{{ __('admin.garden.empty') }}</p>
                @endforelse
            </x-ui.card>
        @endforeach
    </div>
</div>
