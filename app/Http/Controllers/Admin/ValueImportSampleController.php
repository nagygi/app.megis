<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Value;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The sample table of the Érték Import (03 · 3.6): three values and, on its
 * second sheet, the column guide. Every admin may download it. It is the
 * docs/ copy the command's tests load, so it stays loadable.
 */
class ValueImportSampleController extends Controller
{
    public const FILE = 'docs/ertektar-import-minta.xlsx';

    public function __invoke(): BinaryFileResponse
    {
        Gate::authorize('viewAny', Value::class);

        return response()->download(base_path(self::FILE), basename(self::FILE));
    }
}
