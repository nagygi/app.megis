<?php

namespace App\Console\Commands;

use App\Services\Import\SpreadsheetReader;
use App\Services\Values\ValueImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Loads the starting values of the Értéktár from a table (03 · 3.6).
 * The column format is in docs/install.md.
 */
#[Signature('megis:values:import {file : Az xlsx vagy csv fájl útvonala} {--dry-run : Csak ellenőriz, nem tölt be semmit}')]
#[Description('Kezdő értékek betöltése az Értéktárba táblázatból (03 · 3.6)')]
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

        foreach ($result->skipped as $name) {
            $this->warn(__('console.values_import.skipped', ['name' => $name]));
        }

        if ($result->failed()) {
            foreach ($result->errors as $error) {
                $this->error($error);
            }

            $this->error(__('console.values_import.failed'));

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->info(__('console.values_import.dry_run', ['count' => $result->wouldImport]));

            return self::SUCCESS;
        }

        foreach ($result->createdCategories as $category) {
            $this->line(__('console.values_import.category_created', ['name' => $category]));
        }

        $this->info(__('console.values_import.imported', ['count' => $result->imported]));

        return self::SUCCESS;
    }
}
