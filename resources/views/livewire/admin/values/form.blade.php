<div class="max-w-3xl">
    <x-ui.flash class="mb-6" />

    @if ($value)
        @include('livewire.admin.values.partials.header', ['value' => $value])
    @else
        <x-admin.page-header :title="$title" />
    @endif

    <form wire:submit="save" class="flex flex-col gap-6 rounded-card border border-grey-250 bg-white p-6">
        <x-form.input name="name" wire:model.live.debounce.400ms="name" :label="__('admin.values.fields.name')" required />

        <div class="flex items-end gap-2">
            <div class="flex-1">
                <x-form.input
                    name="slug"
                    wire:model="slug"
                    :label="__('admin.values.fields.slug')"
                    :help="__('admin.values.slug_help')"
                    :readonly="$slugLocked"
                    required
                />
            </div>
            @if ($slugLocked)
                <x-ui.button tone="outline" size="sm" wire:click="unlockSlug" class="mb-7">{{ __('admin.values.slug_edit') }}</x-ui.button>
            @endif
        </div>

        <x-form.select name="value_category_id" wire:model="value_category_id" :label="__('admin.values.fields.category')">
            <option value="">{{ __('admin.values.category_none') }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </x-form.select>

        <x-admin.list-editor name="meanings" :items="$meanings" :label="__('admin.values.fields.meanings')" :help="__('admin.values.help.meanings')" />
        <x-admin.list-editor name="non_meanings" :items="$non_meanings" :label="__('admin.values.fields.non_meanings')" />
        <x-admin.list-editor name="high_examples" :items="$high_examples" :label="__('admin.values.fields.high_examples')" :help="__('admin.values.help.high_examples')" marker="6" marker-tone="green" required />
        <x-admin.list-editor name="low_examples" :items="$low_examples" :label="__('admin.values.fields.low_examples')" :help="__('admin.values.help.low_examples')" marker="1" marker-tone="coral" required />
        <x-admin.list-editor name="examples" :items="$examples" :label="__('admin.values.fields.examples')" :help="__('admin.values.help.examples')" />
        <x-admin.list-editor name="keywords" :items="$keywords" :label="__('admin.values.fields.keywords')" :help="__('admin.values.help.keywords')" />

        <div class="flex flex-wrap gap-3">
            <x-ui.button type="submit">{{ __('admin.common.save') }}</x-ui.button>
            <x-ui.button tone="outline" :href="route('admin.values.index')">{{ __('admin.common.back') }}</x-ui.button>
        </div>
    </form>

    @if ($occurrences->isNotEmpty())
        <section class="mt-6 flex flex-col gap-3" aria-labelledby="occurrences">
            <h2 id="occurrences" class="text-lg font-semibold">{{ __('admin.values.occurrences') }}</h2>
            <ul class="flex flex-col gap-2">
                @foreach ($occurrences as $occurrence)
                    @php($occurrenceDiagnosis = $occurrence->material?->diagnoses->first(fn ($diagnosis) => $diagnosis->speaker === null || $diagnosis->speaker === $occurrence->speaker))
                    <li wire:key="occurrence-{{ $occurrence->id }}" class="rounded-card border border-grey-250 bg-white p-4 text-sm">
                        <p>„{{ $occurrence->quote }}”</p>
                        <p class="mt-1 text-grey-600">
                            {{ $occurrence->created_at->format('Y.m.d.') }}
                            @if ($occurrence->speaker) · {{ $occurrence->speaker }}@endif
                            @if ($occurrenceDiagnosis)
                                · <a href="{{ route('admin.diagnoses.show', $occurrenceDiagnosis) }}" class="text-charcoal">{{ $occurrenceDiagnosis->name }}</a>
                            @endif
                        </p>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
