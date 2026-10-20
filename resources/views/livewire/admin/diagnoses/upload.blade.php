<div class="max-w-3xl">
    <x-admin.page-header :title="__('admin.upload.title')" />

    <x-ui.card as="form" bordered padding="lg" wire:submit="save" class="flex flex-col gap-5">
        <x-form.select name="organization_id" wire:model.live="organization_id" :label="__('admin.diagnoses.organization')" required>
            <option value="">{{ __('admin.diagnoses.organization_select') }}</option>
            <x-admin.organization-options :organizations="$organizations" />
        </x-form.select>

        <x-form.select name="diagnosis_type_id" wire:model="diagnosis_type_id" :label="__('admin.diagnoses.type')" required>
            @foreach ($types as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        </x-form.select>

        <x-form.input name="diagnosed_on" type="date" wire:model="diagnosed_on" :label="__('admin.diagnoses.date')" required />

        @if ($mainDiagnoses->isNotEmpty())
            <x-form.select name="parent_diagnosis_id" wire:model="parent_diagnosis_id" :label="__('admin.main_diagnoses.parent')" :help="__('admin.main_diagnoses.parent_help')">
                <option value="">{{ __('admin.main_diagnoses.parent_none') }}</option>
                @foreach ($mainDiagnoses as $mainOption)
                    <option value="{{ $mainOption->id }}">{{ $mainOption->name }}</option>
                @endforeach
            </x-form.select>
        @endif

        <div class="flex flex-col gap-1.5">
            <x-form.file name="file" id="upload-file" wire:model="file" :label="__('admin.upload.file')" :help="__('admin.upload.rules', $rules)" required />
            <div wire:loading wire:target="file" class="text-sm" role="status">{{ __('admin.upload.uploading') }}</div>
        </div>

        <p class="text-sm text-grey-700">{{ __('admin.upload.speakers_next') }}</p>

        <div class="flex flex-wrap gap-3">
            <x-ui.button type="submit" icon="upload" wire:loading.attr="disabled" wire:target="file,save">{{ __('admin.upload.submit') }}</x-ui.button>
            <x-ui.button tone="outline" :href="route('admin.diagnoses.index')">{{ __('admin.common.cancel') }}</x-ui.button>
        </div>
    </x-ui.card>
</div>
