{{-- The same width as the Verziók tab, so the header does not jump between the tabs. --}}
<div class="max-w-5xl">
    <x-ui.flash class="mb-6" />

    @if ($value)
        @include('livewire.admin.values.partials.header', ['value' => $value])
    @else
        <x-admin.page-header :title="$title" />
    @endif

    {{-- One card per section, so the long form reads in parts. --}}
    <form wire:submit="save" class="flex flex-col gap-6">
        {{-- Azonosítás --}}
        <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-5" aria-labelledby="value-identity">
            <x-ui.section-header id="value-identity" :title="__('admin.values.sections.identity')" />

            <div class="grid gap-5 md:grid-cols-2">
                <x-form.input name="name" wire:model.live.debounce.400ms="name" :label="__('admin.values.fields.name')" required />
                <x-form.input name="name_en" wire:model="name_en" :label="__('admin.values.fields.name_en')" />

                <x-form.input
                    name="code"
                    wire:model="code"
                    :label="__('admin.values.fields.code')"
                    :help="__('admin.values.help.code')"
                    :readonly="$value?->code !== null"
                />

                <x-form.inline>
                    <x-form.input
                        name="slug"
                        wire:model="slug"
                        :label="__('admin.values.fields.slug')"
                        :help="__('admin.values.slug_help')"
                        :readonly="$slugLocked"
                        required
                    />
                    <x-slot:actions>
                        @if ($slugLocked)
                            <x-ui.button tone="outline" size="sm" wire:click="unlockSlug">{{ __('admin.values.slug_edit') }}</x-ui.button>
                        @endif
                    </x-slot:actions>
                </x-form.inline>

                <x-form.select name="value_category_id" wire:model="value_category_id" :label="__('admin.values.fields.category')">
                    <option value="">{{ __('admin.values.category_none') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->label() }}</option>
                    @endforeach
                </x-form.select>
            </div>
        </x-ui.card>

        {{-- Definíció és határ --}}
        <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-5" aria-labelledby="value-definition">
            <x-ui.section-header id="value-definition" :title="__('admin.values.sections.definition')" />

            <x-form.textarea name="definition" wire:model="definition" :label="__('admin.values.fields.definition')" :help="__('admin.values.help.definition')" rows="2" />
            <x-form.textarea name="definition_en" wire:model="definition_en" :label="__('admin.values.fields.definition_en')" rows="2" />
            <x-admin.list-editor name="meanings" :items="$meanings" :label="__('admin.values.fields.meanings')" :help="__('admin.values.help.meanings')" />
            <x-admin.list-editor name="non_meanings" :items="$non_meanings" :label="__('admin.values.fields.non_meanings')" />
            <x-admin.list-editor name="false_positives" :items="$false_positives" :label="__('admin.values.fields.false_positives')" :help="__('admin.values.help.false_positives')" />
            <x-admin.list-editor name="overlaps" :items="$overlaps" :label="__('admin.values.fields.overlaps')" :help="__('admin.values.help.overlaps')" />
        </x-ui.card>

        {{-- Skála --}}
        <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-5" aria-labelledby="value-scale">
            <x-ui.section-header id="value-scale" :title="__('admin.values.sections.scale')" />

            <x-admin.list-editor name="high_examples" :items="$high_examples" :label="__('admin.values.fields.high_examples')" :help="__('admin.values.help.high_examples')" marker="6" marker-tone="green" required />
            <x-admin.list-editor name="low_examples" :items="$low_examples" :label="__('admin.values.fields.low_examples')" :help="__('admin.values.help.low_examples')" marker="1" marker-tone="coral" required />
        </x-ui.card>

        {{-- Felismerés --}}
        <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-5" aria-labelledby="value-recognition">
            <x-ui.section-header id="value-recognition" :title="__('admin.values.sections.recognition')" />

            <x-admin.list-editor name="examples" :items="$examples" :label="__('admin.values.fields.examples')" :help="__('admin.values.help.examples')" />
            <x-admin.list-editor name="keywords" :items="$keywords" :label="__('admin.values.fields.keywords')" :help="__('admin.values.help.keywords')" />
        </x-ui.card>

        {{-- Szakirodalom és minősítés --}}
        <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-5" aria-labelledby="value-literature">
            <x-ui.section-header id="value-literature" :title="__('admin.values.sections.literature')" />

            <x-admin.list-editor name="literature" :items="$literature" :label="__('admin.values.fields.literature')" :help="__('admin.values.help.literature')" :max-length="\App\Models\Value::LITERATURE_ITEM_MAX_LENGTH" />

            <div class="grid gap-5 md:grid-cols-3">
                @foreach (['evidence' => $grades, 'transposability' => $grades, 'svs_status' => $svsStatuses] as $field => $options)
                    <x-form.select :name="$field" wire:model="{{ $field }}" :label="__('admin.values.fields.'.$field)">
                        <option value="">{{ __('admin.values.not_set') }}</option>
                        @foreach ($options as $option => $label)
                            <option value="{{ $option }}">{{ $label }}</option>
                        @endforeach
                    </x-form.select>
                @endforeach
            </div>
        </x-ui.card>

        <div class="flex flex-wrap gap-3">
            <x-ui.button type="submit">{{ __('admin.common.save') }}</x-ui.button>
            <x-ui.button tone="outline" :href="route('admin.values.index')">{{ __('admin.common.cancel') }}</x-ui.button>
        </div>
    </form>

    @if ($occurrences->isNotEmpty())
        <section class="mt-6 flex flex-col gap-3" aria-labelledby="occurrences">
            <h2 id="occurrences" class="text-lg font-semibold">{{ __('admin.values.occurrences') }}</h2>
            <ul class="flex flex-col gap-2">
                @foreach ($occurrences as $occurrence)
                    @php($occurrenceDiagnosis = $occurrence->material?->diagnoses->first(fn ($diagnosis) => $diagnosis->speaker === null || $diagnosis->speaker === $occurrence->speaker))
                    <x-ui.card as="li" bordered padding="sm" wire:key="occurrence-{{ $occurrence->id }}" class="text-sm">
                        <p>„{{ $occurrence->quote }}”</p>
                        <p class="mt-1 text-grey-700">
                            {{ $occurrence->created_at->format('Y.m.d.') }}
                            @if ($occurrence->speaker) · {{ $occurrence->speaker }}@endif
                            @if ($occurrenceDiagnosis)
                                · <a href="{{ route('admin.diagnoses.show', $occurrenceDiagnosis) }}" class="text-charcoal">{{ $occurrenceDiagnosis->name }}</a>
                            @endif
                        </p>
                    </x-ui.card>
                @endforeach
            </ul>
        </section>
    @endif
</div>
