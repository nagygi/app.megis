<?php

namespace App\Livewire\Client\Diagnosis\Concerns;

use App\Enums\MaterialContext;
use App\Models\Material;
use App\Services\Processes\OccasionService;

/**
 * The occasion pages of a running diagnosis (12): who may add, and the
 * user's own materials, never anyone else's (12, "a feltöltő a sajátjait látja").
 */
trait HandlesOccasionMaterials
{
    protected function authorizeContribution(): void
    {
        abort_unless(app(OccasionService::class)->canContribute($this->main(), $this->user()), 403, __('diagnosis.locked'));
    }

    protected function ownMaterial(string $uuid): Material
    {
        return Material::query()
            ->where('uuid', $uuid)
            ->where('parent_diagnosis_id', $this->diagnosis->id)
            ->where('user_id', $this->user()->id)
            ->where('context', MaterialContext::Occasion)
            ->firstOrFail();
    }

    protected function contributeUrl(): string
    {
        return route('client.diagnosis.contribute', [$this->organization, $this->diagnosis]);
    }
}
