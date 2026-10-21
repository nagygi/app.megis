<?php

namespace App\Services\Capabilities;

use App\Enums\MaterialContext;
use App\Models\MainDiagnosis;
use App\Models\Material;

/**
 * Which materials the capability measurement reads (NM-12): every material
 * of a diagnosis (Teljes, Snapshot, an admin upload, a signed-in mirror),
 * by the switches of config/megis.php and config/diagnosis.php. Not the
 * public mirror, a dictation or an occasion outside a diagnosis.
 */
class CapabilityMeasurementPolicy
{
    public function appliesTo(Material $material): bool
    {
        if (! config('megis.capabilities.measurement.enabled') || $material->organization_id === null) {
            return false;
        }

        if (in_array($material->context, [MaterialContext::PublicMirror, MaterialContext::Dictation], true)) {
            return false;
        }

        if ($material->parent_diagnosis_id !== null) {
            $main = MainDiagnosis::query()->where('diagnosis_id', $material->parent_diagnosis_id)->first();

            return $main === null || (bool) config('diagnosis.products.'.$main->product_key.'.capability_measurement', true);
        }

        return in_array($material->context, [MaterialContext::Mirror, MaterialContext::AdminUpload], true)
            && (bool) config('megis.capabilities.measurement.plain_diagnoses');
    }
}
