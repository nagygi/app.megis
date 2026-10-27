<?php

namespace App\Services\Report;

use App\Models\Diagnosis;
use App\Models\Material;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A report PDF the admin uploads: "Letöltöm · PDF" gives it instead of the
 * generated one (28 · 28.3). It belongs to the diagnosis, so a correction or a
 * republication keeps it.
 */
class ReportPdfStore
{
    public const DISK = Material::DISK;

    /**
     * Stores the file and replaces the previous one.
     */
    public function store(Diagnosis $diagnosis, UploadedFile $file): void
    {
        $previous = $diagnosis->report_pdf_path;
        $path = $file->storeAs($this->directory($diagnosis), Str::uuid().'.pdf', ['disk' => self::DISK]);

        $diagnosis->update([
            'report_pdf_path' => $path,
            'report_pdf_name' => $file->getClientOriginalName(),
            'report_pdf_uploaded_at' => now(),
        ]);

        if ($previous !== null) {
            Storage::disk(self::DISK)->delete($previous);
        }
    }

    /**
     * "Törlöm": the download gives the generated PDF again.
     */
    public function remove(Diagnosis $diagnosis): void
    {
        $this->forget($diagnosis);

        $diagnosis->update([
            'report_pdf_path' => null,
            'report_pdf_name' => null,
            'report_pdf_uploaded_at' => null,
        ]);
    }

    /**
     * The file alone: a deleted diagnosis takes its PDF with it.
     */
    public function forget(Diagnosis $diagnosis): void
    {
        if ($diagnosis->report_pdf_path !== null) {
            Storage::disk(self::DISK)->deleteDirectory($this->directory($diagnosis));
        }
    }

    public function response(Diagnosis $diagnosis, string $filename, string $disposition = 'attachment'): StreamedResponse
    {
        return Storage::disk(self::DISK)->response($diagnosis->report_pdf_path, $filename, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store',
        ], $disposition);
    }

    private function directory(Diagnosis $diagnosis): string
    {
        return 'reports/'.$diagnosis->id;
    }
}
