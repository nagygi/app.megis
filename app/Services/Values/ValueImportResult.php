<?php

namespace App\Services\Values;

/**
 * What an Értéktár import did, or would do on a dry run.
 */
class ValueImportResult
{
    public int $imported = 0;

    public int $wouldImport = 0;

    /** @var list<string> names already in the Értéktár */
    public array $skipped = [];

    /** @var list<string> */
    public array $createdCategories = [];

    /** @var list<string> */
    public array $ignoredColumns = [];

    /** @var list<string> */
    public array $errors = [];

    public function failed(): bool
    {
        return $this->errors !== [];
    }
}
