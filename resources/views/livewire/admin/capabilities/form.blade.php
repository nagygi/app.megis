@php($marks = fn (string $field) => view('livewire.admin.capabilities.partials.marks', ['capability' => $capability, 'field' => $field]))

{{-- The same width as the Verziók tab, so the header does not jump between the tabs. --}}
<div class="max-w-5xl">
    <x-ui.flash class="mb-6" />

    @if ($capability)
        @include('livewire.admin.capabilities.partials.header', ['capability' => $capability])
    @else
        <x-admin.page-header :title="$title" />
    @endif

    {{-- About 25 fields: one card per section, so the form reads in parts. --}}
    <form wire:submit="save" class="flex flex-col gap-6">
        {{-- Azonosítás --}}
        <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-5" aria-labelledby="capability-identity">
            <x-ui.section-header id="capability-identity" :title="__('admin.capabilities.sections.identity')" />

            <div class="grid gap-5 md:grid-cols-2">
                <x-form.input
                    name="code"
                    wire:model="code"
                    :label="__('admin.capabilities.fields.code')"
                    :help="__('admin.capabilities.help.code')"
                    :readonly="$capability !== null"
                    required
                />

                <div class="flex flex-col gap-1 md:col-start-1">
                    <x-form.input name="name" wire:model.live.debounce.400ms="name" :label="__('admin.capabilities.fields.name')" required />
                    {{ $marks('name') }}
                </div>
                <div class="flex flex-col gap-1">
                    <x-form.input name="name_en" wire:model="name_en" :label="__('admin.capabilities.fields.name_en')" />
                    {{ $marks('name_en') }}
                </div>

                <x-form.inline>
                    <x-form.input
                        name="slug"
                        wire:model="slug"
                        :label="__('admin.capabilities.fields.slug')"
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
            </div>
        </x-ui.card>

        {{-- Besorolás --}}
        <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-5" aria-labelledby="capability-classification">
            <x-ui.section-header id="capability-classification" :title="__('admin.capabilities.sections.classification')" />

            <div class="grid gap-5 md:grid-cols-2">
                <div class="flex flex-col gap-1">
                    <x-form.select name="capability_group_id" wire:model="capability_group_id" :label="__('admin.capabilities.fields.group')" required>
                        <option value="">{{ __('admin.capabilities.choose') }}</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->label() }}</option>
                        @endforeach
                    </x-form.select>
                    {{ $marks('group') }}
                </div>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                @foreach (['level' => $levels, 'status' => $statuses] as $field => $options)
                    <div class="flex flex-col gap-1">
                        <x-form.select :name="$field" wire:model="{{ $field }}" :label="__('admin.capabilities.fields.'.$field)" :help="$field === 'status' ? __('admin.capabilities.help.status') : null" required>
                            <option value="">{{ __('admin.capabilities.choose') }}</option>
                            @foreach ($options as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-form.select>
                        {{ $marks($field) }}
                    </div>
                @endforeach

                <div class="flex flex-col gap-1">
                    <x-form.select name="nature" wire:model="nature" :label="__('admin.capabilities.fields.nature')">
                        <option value="">{{ __('admin.capabilities.not_set') }}</option>
                        @foreach ($natures as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-form.select>
                    {{ $marks('nature') }}
                </div>

                <div class="flex flex-col gap-1">
                    <x-form.select name="tempting" wire:model="tempting" :label="__('admin.capabilities.fields.tempting')" :help="__('admin.capabilities.help.tempting')">
                        <option value="">{{ __('admin.capabilities.not_set') }}</option>
                        <option value="1">{{ __('admin.capabilities.tempting_yes') }}</option>
                        <option value="0">{{ __('admin.capabilities.tempting_no') }}</option>
                    </x-form.select>
                    {{ $marks('tempting') }}
                </div>
            </div>
        </x-ui.card>

        {{-- Definíció és határ --}}
        <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-5" aria-labelledby="capability-definition">
            <x-ui.section-header id="capability-definition" :title="__('admin.capabilities.sections.definition')" />

            <div class="flex flex-col gap-1">
                <x-form.textarea name="definition" wire:model="definition" :label="__('admin.capabilities.fields.definition')" :help="__('admin.capabilities.help.definition')" rows="3" required />
                {{ $marks('definition') }}
            </div>
            <div class="flex flex-col gap-1">
                <x-form.textarea name="statement" wire:model="statement" :label="__('admin.capabilities.fields.statement')" :help="__('admin.capabilities.help.statement')" rows="2" />
                {{ $marks('statement') }}
            </div>

            <div class="flex flex-col gap-1">
                <x-admin.list-editor name="non_examples" :items="$non_examples" :label="__('admin.capabilities.fields.non_examples')" :help="__('admin.capabilities.help.non_examples')" :max-length="\App\Models\Capability::ITEM_MAX_LENGTH" />
                {{ $marks('non_examples') }}
            </div>
            <div class="flex flex-col gap-1">
                <x-admin.list-editor name="overlaps" :items="$overlaps" :label="__('admin.capabilities.fields.overlaps')" :help="__('admin.capabilities.help.overlaps')" :max-length="\App\Models\Capability::ITEM_MAX_LENGTH" />
                {{ $marks('overlaps') }}
            </div>
        </x-ui.card>

        {{-- Megjelenés szintenként --}}
        <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-5" aria-labelledby="capability-appearances">
            <x-ui.section-header id="capability-appearances" :title="__('admin.capabilities.fields.appearances')" />

            @foreach (\App\Enums\CapabilityLevel::cases() as $level)
                <div class="flex flex-col gap-1" wire:key="appearance-{{ $level->value }}">
                    <x-form.textarea :name="'appearances.'.$level->value" wire:model="appearances.{{ $level->value }}" :label="__('admin.capabilities.fields.appearance_'.$level->value)" rows="2" />
                    {{ $marks('appearance_'.$level->value) }}
                </div>
            @endforeach
        </x-ui.card>

        {{-- Felismerés --}}
        <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-5" aria-labelledby="capability-recognition">
            <x-ui.section-header id="capability-recognition" :title="__('admin.capabilities.sections.recognition')" />

            <fieldset class="flex flex-col gap-2">
                <legend class="mb-1 flex flex-wrap items-center gap-2 text-sm font-medium">
                    {{ __('admin.capabilities.fields.preconditions') }}
                    {{ $marks('preconditions') }}
                </legend>
                @foreach ($preconditions as $precondition)
                    <div wire:key="precondition-{{ $precondition->id }}">
                        <x-form.checkbox
                            name="preconditionIds"
                            :id="'precondition-'.$precondition->id"
                            :value="$precondition->id"
                            wire:model="preconditionIds"
                            :label="$precondition->label()"
                            :help="collect([$precondition->description, isset($preconditionNotes[$precondition->id]) ? __('admin.capabilities.precondition_note', ['note' => $preconditionNotes[$precondition->id]]) : null])->filter()->implode(' ')"
                        />
                    </div>
                @endforeach
            </fieldset>

            <x-form.select name="dependencyIds" wire:model="dependencyIds" :label="__('admin.capabilities.fields.dependencies')" :help="__('admin.capabilities.help.dependencies')" multiple size="6">
                @foreach ($dependencyOptions as $option)
                    <option value="{{ $option->id }}">{{ $option->code }} · {{ $option->name }}</option>
                @endforeach
            </x-form.select>

            <x-admin.list-editor name="keywords" :items="$keywords" :label="__('admin.capabilities.fields.keywords')" :help="__('admin.capabilities.help.keywords')" />
        </x-ui.card>

        {{-- Lépcsők --}}
        <x-ui.card as="section" bordered padding="lg" class="flex flex-col gap-5" aria-labelledby="capability-stages">
            <x-ui.section-header id="capability-stages" :title="__('admin.capabilities.fields.stages')" />

            @foreach (\App\Models\Capability::STAGES as $stage)
                <div class="flex flex-col gap-1" wire:key="stage-{{ $stage }}">
                    <x-form.textarea :name="'stages.'.$stage" wire:model="stages.{{ $stage }}" :label="__('admin.capabilities.fields.stage_'.$stage)" rows="2" />
                    {{ $marks('stage_'.$stage) }}
                </div>
            @endforeach
        </x-ui.card>

        {{-- The note belongs to the version this save makes, so it sits with the buttons. --}}
        <x-ui.card bordered padding="lg" class="flex flex-col gap-5">
            <x-form.input name="note" wire:model="note" :label="__('admin.capabilities.fields.note')" :help="__('admin.capabilities.help.note')" />

            <div class="flex flex-wrap gap-3">
                <x-ui.button type="submit">{{ __('admin.common.save') }}</x-ui.button>
                <x-ui.button tone="outline" :href="route('admin.capabilities.index')">{{ __('admin.common.cancel') }}</x-ui.button>
            </div>
        </x-ui.card>
    </form>

    @if ($capability)
        {{-- The other fields of the Sheet, read only (07 · 7.2) --}}
        <x-ui.card as="section" bordered padding="lg" class="mt-6 flex flex-col gap-3" aria-labelledby="capability-details">
            <x-ui.section-header id="capability-details" :title="__('admin.capabilities.fields.details')" :description="__('admin.capabilities.help.details')" />

            @if ($capability->details === [])
                <p class="text-grey-700">{{ __('admin.capabilities.no_details') }}</p>
            @else
                <dl class="flex flex-col gap-3">
                    @foreach ($capability->details as $detail)
                        <div>
                            <dt class="text-sm font-medium">{{ $detail['label'] }}</dt>
                            <dd class="whitespace-pre-line text-grey-700">{{ $detail['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </x-ui.card>
    @endif
</div>
