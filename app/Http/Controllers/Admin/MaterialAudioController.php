<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Services\Materials\MaterialStore;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Plays a recording for an admin: never public, a short-lived link or a
 * private stream (06 · 6.5, 6.6).
 */
class MaterialAudioController extends Controller
{
    public function __invoke(Material $material, MaterialStore $materials): Response
    {
        Gate::authorize('view', $material);

        return $materials->audioResponse($material);
    }
}
