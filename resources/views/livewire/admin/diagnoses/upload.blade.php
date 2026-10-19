<div class="max-w-2xl">
    <x-admin.page-header :title="__('admin.upload.title')" />

    <form wire:submit="save" class="flex flex-col gap-5 rounded-card border border-grey-250 bg-white p-6">
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
            <label for="upload-file" class="text-sm font-medium">{{ __('admin.upload.file') }} <span class="font-normal text-grey-600">· {{ __('ui.required') }}</span></label>
            <input id="upload-file" type="file" wire:model="file" aria-describedby="upload-file-help" class="text-base">
            <p id="upload-file-help" class="text-sm text-grey-600">{{ __('admin.upload.rules', $rules) }}</p>
            <div wire:loading wire:target="file" class="text-sm">{{ __('admin.upload.uploading') }}</div>
            <x-form.error name="file" id="file" />
        </div>

        <p class="text-sm text-grey-700">{{ __('admin.upload.speakers_next') }}</p>

        <div class="flex flex-wrap gap-3">
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="file,save">{{ __('admin.upload.submit') }}</x-ui.button>
            <x-ui.button tone="outline" :href="route('admin.diagnoses.index')">{{ __('admin.common.cancel') }}</x-ui.button>
        </div>
    </form>
</div>
