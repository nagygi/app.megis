<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalDocumentVersion;
use App\Services\Legal\LegalDocuments;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Opens any version, including past and scheduled ones, for the admin (05 · 5.1).
 */
class LegalDocumentVersionController extends Controller
{
    public function __invoke(LegalDocumentVersion $version): StreamedResponse
    {
        Gate::authorize('view', $version);

        return Storage::disk(LegalDocuments::DISK)
            ->response($version->file_path, $version->original_name, [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'private, no-store',
            ], 'inline');
    }
}
