<?php

namespace App\Http\Controllers;

use App\Enums\LegalDocumentType;
use App\Services\Legal\LegalDocuments;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Permanent public URLs that always serve the version in force (05 · 5.2).
 */
class LegalDocumentController extends Controller
{
    public function __invoke(string $document, LegalDocuments $legalDocuments): StreamedResponse
    {
        $type = LegalDocumentType::from($document);
        $version = $legalDocuments->current($type);

        abort_if($version === null, 404);

        return Storage::disk(LegalDocuments::DISK)
            ->response($version->file_path, $type->value.'.pdf', [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'public, max-age=300',
            ], 'inline');
    }
}
