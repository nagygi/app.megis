<?php

namespace App\Services\Checks;

use App\Enums\LegalDocumentType;
use App\Services\Legal\LegalDocuments;

/**
 * All three legal documents must be uploaded (05 · 5.1, docs/uzemeltetes.md · 7).
 * Missing documents are an error in production and a warning elsewhere.
 */
class LegalDocumentsCheck implements Check
{
    public function __construct(private LegalDocuments $legalDocuments) {}

    public function run(): CheckResult
    {
        $label = __('checks.legal_documents.label');

        $missing = collect(LegalDocumentType::cases())
            ->filter(fn (LegalDocumentType $type): bool => $this->legalDocuments->current($type) === null)
            ->map(fn (LegalDocumentType $type): string => $type->label());

        if ($missing->isEmpty()) {
            return CheckResult::ok($label, __('checks.legal_documents.ok'));
        }

        $message = __('checks.legal_documents.missing', ['documents' => $missing->implode(', ')]);

        return app()->isProduction()
            ? CheckResult::error($label, $message)
            : CheckResult::warning($label, $message);
    }
}
