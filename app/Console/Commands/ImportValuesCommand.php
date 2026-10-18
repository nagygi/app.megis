<?php

namespace App\Console\Commands;

use App\Services\Import\SpreadsheetReader;
use App\Services\Values\ValueImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Loads and updates the values of the Értéktár from a table (03 · 3.6).
 * The column format is in docs/install.md.
 */
#[Signature('megis:values:import {file : Az xlsx vagy csv fájl útvonala} {--dry-run : Csak ellenőriz, nem tölt be semmit}')]
#[Description('Értékek betöltése és frissítése az Értéktárban táblázatból (03 · 3.6)')]
class ImportValuesCommand extends Command
{
    public function handle(ValueImporter $importer): int
    {
        $path = (string) $this->argument('file');
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! is_file($path) || ! in_array($extension, SpreadsheetReader::supportedExtensions(), true)) {
            $this->error(__('console.values_import.invalid_file', ['path' => $path]));

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $result = $importer->import($path, $dryRun);

        foreach ($result->ignoredColumns as $column) {
            $this->warn(__('console.values_import.ignored_column', ['column' => $column]));
        }

        if ($result->failed()) {
            foreach ($result->errors as $error) {
                $this->error($error);
            }

            $this->error(__('console.values_import.failed'));

            return self::FAILURE;
        }

        foreach ($result->warnings as $warning) {
            $this->warn($warning);
        }

        foreach ($result->createdCategories as $category) {
            $this->line(__('console.values_import.category_created', ['name' => $category]));
        }

        foreach ($result->updatedCategories as $category) {
            $this->line(__('console.values_import.category_updated', ['name' => $category]));
        }

        foreach ($result->updated as $value) {
            $this->line(__('console.values_import.updated_value', [
                'name' => $value['name'],
                'fields' => implode(', ', array_map(fn (string $field): string => __('admin.values.fields.'.$field), $value['fields'])),
            ]));
        }

        foreach ($result->reviewed as $name) {
            $this->line(__('console.values_import.reviewed', ['name' => $name]));
        }

        $counts = ['created' => count($result->created), 'updated' => count($result->updated), 'unchanged' => count($result->unchanged)];

        if ($dryRun) {
            $this->info(__('console.values_import.dry_run', $counts));

            return self::SUCCESS;
        }

        $this->info(__('console.values_import.imported', ['count' => $counts['created']]));

        if ($counts['updated'] + $counts['unchanged'] > 0) {
            $this->info(__('console.values_import.updated', $counts));
        }

        return self::SUCCESS;
    }
}
