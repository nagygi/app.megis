@php($marks = fn (string $field) => view('livewire.admin.capabilities.partials.marks', ['capability' => $capability, 'field' => $field]))

<div class="max-w-3xl">
    <x-ui.flash class="mb-6" />

    @if ($capability)
        @include('livewire.admin.capabilities.partials.header', ['capability' => $capability])
    @else
        <x-admin.page-header :title="$title" />
    @endif

    <form wire:submit="save" class="flex flex-col gap-8 rounded-card border border-grey-250 bg-white p-6">
        {{-- Azonosítás --}}
        <section class="flex flex-col gap-5" aria-labelledby="capability-identity">
            <h2 id="capability-identity" class="text-lg font-semibold">{{ __('admin.capabilities.sections.identity') }}</h2>

            <x-form.input
                name="code"
                wire:model="code"
                :label="__('admin.capabilities.fields.code')"
                :help="__('admin.capabilities.help.code')"
                :readonly="$capability !== null"
                required
            />

            <div class="flex flex-col gap-1">
                <x-form.input name="name" wire:model.live.debounce.400ms="name" :label="__('admin.capabilities.fields.name')" required />
                {{ $marks('name') }}
            </div>
            <div class="flex flex-col gap-1">
                <x-form.input name="name_en" wire:model="name_en" :label="__('admin.capabilities.fields.name_en')" />
                {{ $marks('name_en') }}
            </div>

            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <x-form.input
                        name="slug"
                        wire:model="slug"
                        :label="__('admin.capabilities.fields.slug')"
                        :help="__('admin.values.slug_help')"
                        :readonly="$slugLocked"
                        required
                    />
                </div>
                @if ($slugLocked)
                    <x-ui.button tone="outline" size="sm" wire:click="unlockSlug" class="mb-7">{{ __('admin.values.slug_edit') }}</x-ui.button>
                @endif
            </div>

            <div class="flex flex-col gap-1">
                <x-form.select name="capability_group_id" wire:model="capability_group_id" :label="__('admin.capabilities.fields.group')" required>
                    <option value="">{{ __('admin.capabilities.choose') }}</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->id }}">{{ $group->label() }}</option>
                    @endforeach
                </x-form.select>
                {{ $marks('group') }}
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
        </section>

        {{-- Definíció és határ --}}
        <section class="flex flex-col gap-5" aria-labelledby="capability-definition">
            <h2 id="capability-definition" class="text-lg font-semibold">{{ __('admin.capabilities.sections.definition') }}</h2>

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
        </section>

        {{-- Megjelenés szintenként --}}
        <section class="flex flex-col gap-5" aria-labelledby="capability-appearances">
            <h2 id="capability-appearances" class="text-lg font-semibold">{{ __('admin.capabilities.fields.appearances') }}</h2>

            @foreach (\App\Enums\CapabilityLevel::cases() as $level)
                <div class="flex flex-col gap-1" wire:key="appearance-{{ $level->value }}">
                    <x-form.textarea :name="'appearances.'.$level->value" wire:model="appearances.{{ $level->value }}" :label="__('admin.capabilities.fields.appearance_'.$level->value)" rows="2" />
                    {{ $marks('appearance_'.$level->value) }}
                </div>
            @endforeach
        </section>

        {{-- Felismerés --}}
        <section class="flex flex-col gap-5" aria-labelledby="capability-recognition">
            <h2 id="capability-recognition" class="text-lg font-semibold">{{ __('admin.capabilities.sections.recognition') }}</h2>

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
        </section>

        {{-- Lépcsők --}}
        <section class="flex flex-col gap-5" aria-labelledby="capability-stages">
            <h2 id="capability-stages" class="text-lg font-semibold">{{ __('admin.capabilities.fields.stages') }}</h2>

            @foreach (\App\Models\Capability::STAGES as $stage)
                <div class="flex flex-col gap-1" wire:key="stage-{{ $stage }}">
                    <x-form.textarea :name="'stages.'.$stage" wire:model="stages.{{ $stage }}" :label="__('admin.capabilities.fields.stage_'.$stage)" rows="2" />
                    {{ $marks('stage_'.$stage) }}
                </div>
            @endforeach
        </section>

        <x-form.input name="note" wire:model="note" :label="__('admin.capabilities.fields.note')" :help="__('admin.capabilities.help.note')" />

        <div class="flex flex-wrap gap-3">
            <x-ui.button type="submit">{{ __('admin.common.save') }}</x-ui.button>
            <x-ui.button tone="outline" :href="route('admin.capabilities.index')">{{ __('admin.common.back') }}</x-ui.button>
        </div>
    </form>

    @if ($capability)
        {{-- The other fields of the Sheet, read only (07 · 7.2) --}}
        <section class="mt-6 flex flex-col gap-3 rounded-card border border-grey-250 bg-white p-6" aria-labelledby="capability-details">
            <h2 id="capability-details" class="text-lg font-semibold">{{ __('admin.capabilities.fields.details') }}</h2>
            <p class="text-sm text-grey-600">{{ __('admin.capabilities.help.details') }}</p>

            @if ($capability->details === [])
                <p class="text-grey-600">{{ __('admin.capabilities.no_details') }}</p>
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
        </section>
    @endif
</div>
