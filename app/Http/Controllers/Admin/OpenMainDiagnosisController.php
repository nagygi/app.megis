<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Diagnosis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * A main diagnosis always opens on the tab of its stage (19 · 19.1).
 */
class OpenMainDiagnosisController extends Controller
{
    public function __invoke(Diagnosis $diagnosis): RedirectResponse
    {
        Gate::authorize('view', $diagnosis);
        abort_if($diagnosis->main === null, 404);

        return redirect()->route($diagnosis->main->stageRoute(auth('admin')->user()), $diagnosis);
    }
}
