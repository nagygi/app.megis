<div class="max-w-3xl">
    <x-ui.flash class="mb-6" />

    <x-admin.page-header :title="$title" />

    <x-ui.card as="form" bordered padding="lg" wire:submit="save" class="flex flex-col gap-5">
        <x-form.input name="name" wire:model="name" :label="__('admin.diagnoses.name')" required />
        <x-form.textarea name="description" wire:model="description" :label="__('admin.diagnoses.description')" />

        @if ($diagnosis)
            <div class="flex flex-col gap-1.5">
                <span class="text-sm font-medium">{{ __('admin.diagnoses.organization') }}</span>
                <span>{{ $diagnosis->organization->name }}</span>
                <p class="text-sm text-grey-700">{{ __('admin.diagnoses.organization_fixed') }}</p>
            </div>
        @else
            <x-form.select name="organization_id" wire:model.live="organization_id" :label="__('admin.diagnoses.organization')" required>
                <option value="">{{ __('admin.diagnoses.organization_select') }}</option>
                <x-admin.organization-options :organizations="$organizations" />
            </x-form.select>
        @endif

        <x-form.radio-group
            name="level"
            wire:model.live="level"
            :label="__('admin.diagnoses.level')"
            :options="$levels->mapWithKeys(fn ($levelOption) => [$levelOption->value => $levelOption->label()])->all()"
            :help="$organization?->isIndividual() ? __('admin.diagnoses.level_team_individual') : null"
            required
            inline
        />

        @if ($level === \App\Enums\DiagnosisLevel::Team->value)
            <x-form.select name="team_id" wire:model="team_id" :label="__('admin.diagnoses.team')" required>
                <option value="">{{ __('admin.diagnoses.team_select') }}</option>
                @foreach ($teams as $team)
                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                @endforeach
            </x-form.select>
        @endif

        @if ($level === \App\Enums\DiagnosisLevel::Process->value)
            <x-form.select name="process_id" wire:model="process_id" :label="__('admin.diagnoses.process')" required>
                <option value="">{{ __('admin.diagnoses.process_select') }}</option>
                @foreach ($processes as $processOption)
                    <option value="{{ $processOption->id }}">{{ $processOption->name }}</option>
                @endforeach
            </x-form.select>
        @endif

        @if ($level === \App\Enums\DiagnosisLevel::User->value)
            <x-form.select name="user_id" wire:model="user_id" :label="__('admin.diagnoses.user')" required>
                <option value="">{{ __('admin.diagnoses.user_select') }}</option>
                @foreach ($members as $member)
                    <option value="{{ $member->user_id }}">{{ $member->user->name }} · {{ $member->user->email }}</option>
                @endforeach
            </x-form.select>
        @endif

        <x-form.select name="diagnosis_type_id" wire:model="diagnosis_type_id" :label="__('admin.diagnoses.type')" required>
            <option value="">{{ __('admin.diagnoses.type_select') }}</option>
            @foreach ($types as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        </x-form.select>

        @if ($mainDiagnoses->isNotEmpty())
            <x-form.select name="parent_id" wire:model="parent_id" :label="__('admin.main_diagnoses.parent')" :help="__('admin.main_diagnoses.parent_help')">
                <option value="">{{ __('admin.main_diagnoses.parent_none') }}</option>
                @foreach ($mainDiagnoses as $mainOption)
                    <option value="{{ $mainOption->id }}">{{ $mainOption->name }}</option>
                @endforeach
            </x-form.select>
        @endif

        <x-form.toggle name="isPeriod" wire:model.live="isPeriod" :label="__('admin.diagnoses.period')" :help="__('admin.diagnoses.period_help')" />

        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.input name="diagnosed_on" type="date" wire:model="diagnosed_on" :label="$isPeriod ? __('admin.diagnoses.date_from') : __('admin.diagnoses.date')" required />
            @if ($isPeriod)
                <x-form.input name="diagnosed_until" type="date" wire:model="diagnosed_until" :label="__('admin.diagnoses.date_until')" required />
            @endif
        </div>

        <div class="flex flex-wrap gap-3">
            <x-ui.button type="submit">{{ __('admin.common.save') }}</x-ui.button>
            <x-ui.button tone="outline" :href="$diagnosis ? route('admin.diagnoses.show', $diagnosis) : route('admin.diagnoses.index')">{{ __('admin.common.cancel') }}</x-ui.button>
        </div>
    </x-ui.card>
</div>
