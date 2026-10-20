{{-- A0 · the garden of the main diagnoses, a column per stage (19 · 19.2). --}}
<div class="flex flex-col gap-6">
    <x-ui.flash />

    <x-admin.page-header :title="__('admin.garden.title')" class="mb-0">
        <x-slot:actions>
            <x-ui.button :href="route('admin.main-diagnoses.create')">{{ __('admin.garden.new') }}</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <p class="text-grey-700">
        <a href="{{ route('admin.inbox.index') }}" class="font-semibold">{{ __('admin.garden.today', ['count' => $openCount]) }}</a>
    </p>

    <div class="flex flex-wrap items-end gap-4">
        <div class="flex gap-2 text-sm" role="group" aria-label="{{ __('admin.garden.title') }}">
            <span class="rounded-pill bg-charcoal px-3 py-1 font-semibold text-cream" aria-current="page">{{ __('admin.garden.view_garden') }}</span>
            <a href="{{ route('admin.diagnoses.index', ['nezet' => 'lista']) }}" class="rounded-pill border border-charcoal px-3 py-1 font-semibold text-charcoal no-underline">{{ __('admin.garden.view_list') }}</a>
        </div>
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
        <x-form.checkbox name="showAll" wire:model.live="showAll" :label="__('admin.garden.show_all')" />
    </div>

    <div class="grid gap-4 overflow-x-auto md:grid-flow-col md:auto-cols-[minmax(14rem,1fr)]">
        @foreach ($columns as $column)
            <section class="flex flex-col gap-3 rounded-card bg-paper p-3" aria-labelledby="stage-{{ $column['stage']->value }}">
                <h2 id="stage-{{ $column['stage']->value }}" class="flex items-center gap-2 font-semibold">
                    <x-megis.symbol :name="$column['symbol']" class="size-6" />
                    {{ $column['stage']->label() }}
                    <span class="text-sm font-normal text-grey-600">{{ $column['cards']->count() }}</span>
                </h2>
                @forelse ($column['cards'] as $card)
                    @php($main = $card['main'])
                    <a
                        wire:key="card-{{ $main->id }}"
                        href="{{ route('admin.main-diagnoses.open', $main->diagnosis) }}"
                        @class([
                            'flex flex-col gap-1 rounded-card bg-white p-3 text-charcoal no-underline transition-[translate] duration-200 ease-standard hover:-translate-y-0.75',
                            'border-2 border-coral' => $card['attention'],
                            'border border-grey-250' => ! $card['attention'],
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
                    </a>
                @empty
                    <p class="text-sm text-grey-600">{{ __('admin.garden.empty') }}</p>
                @endforelse
            </section>
        @endforeach
    </div>
</div>
