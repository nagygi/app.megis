<?php

namespace App\Services\Capabilities;

/**
 * What a load from the mégis tár did, or would do in the preview (07 · 7.6).
 */
class CapabilityImportResult
{
    /** @var list<array{code: string, name: string}> */
    public array $created = [];

    /** @var list<array{code: string, name: string, fields: list<string>}> changed fields as snapshot keys */
    public array $updated = [];

    /** @var list<string> codes */
    public array $unchanged = [];

    /** @var list<string> codes archived because the mégis set them aside (07 · 7.5) */
    public array $archived = [];

    /** @var list<string> codes restored because they are no longer set aside */
    public array $restored = [];

    /** @var list<string> */
    public array $createdGroups = [];

    /** @var list<string> rows left out, with the reason */
    public array $errors = [];

    /** @var list<string> loaded, but worth a look */
    public array $warnings = [];

    /** @var list<string> columns of the Képesség sheet that do not come in */
    public array $ignoredColumns = [];

    public function failed(): bool
    {
        return $this->created === [] && $this->updated === [] && $this->unchanged === [] && $this->errors !== [];
    }

    public function hasChanges(): bool
    {
        return $this->created !== [] || $this->updated !== [] || $this->archived !== [] || $this->restored !== [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
