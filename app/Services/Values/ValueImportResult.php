<?php

namespace App\Services\Values;

/**
 * What an Értéktár import did, or would do on a dry run.
 */
class ValueImportResult
{
    /** @var list<string> names of the new values */
    public array $created = [];

    /** @var list<array{name: string, fields: list<string>}> values with a new version */
    public array $updated = [];

    /** @var list<string> names of the values the file left as they were */
    public array $unchanged = [];

    /** @var list<string> names of the values the load marked reviewed */
    public array $reviewed = [];

    /** @var list<string> */
    public array $createdCategories = [];

    /** @var list<string> */
    public array $updatedCategories = [];

    /** @var list<string> */
    public array $ignoredColumns = [];

    /** @var list<string> */
    public array $warnings = [];

    /** @var list<string> */
    public array $errors = [];

    public function failed(): bool
    {
        return $this->errors !== [];
    }
}
