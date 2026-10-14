<?php

namespace App\Services\Values;

use App\Models\ValueCategory;
use App\Services\Import\SpreadsheetReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * One-off load of the starting values (03 · 3.6): one row per value, one
 * column per field of the value card, list items on separate lines of a
 * cell. Every value comes in as v1. Values already in the Értéktár are
 * skipped; when any row is invalid, nothing is loaded.
 */
class ValueImporter
{
    /**
     * Accepted headers, compared without case, punctuation and the 1 / 6 prefix.
     */
    private const COLUMNS = [
        'nev' => 'name',
        'kategoria' => 'category',
        'mit jelent' => 'meanings',
        'mit nem jelent' => 'non_meanings',
        'mikor teljesul nagyon' => 'high_examples',
        'mikor nagyon nem teljesul' => 'low_examples',
        'peldak' => 'examples',
        'pelda' => 'examples',
        'szinonimak kulcsszavak' => 'keywords',
    ];

    private const LISTS = ['meanings', 'non_meanings', 'high_examples', 'low_examples', 'examples', 'keywords'];

    public function __construct(
        private SpreadsheetReader $reader,
        private ValueService $values,
    ) {}

    public function import(string $path, bool $dryRun = false): ValueImportResult
    {
        $sheet = $this->reader->read($path);
        $result = new ValueImportResult;

        $columns = $this->mapColumns($sheet['headers'], $result);

        if (! in_array('name', $columns, true)) {
            $result->errors[] = __('console.values_import.missing_name_column');

            return $result;
        }

        $values = [];
        $namesInFile = [];

        foreach ($sheet['rows'] as $lineNumber => $row) {
            $value = $this->valueFromRow($row, $columns);
            $nameKey = mb_strtolower($value['name']);

            if ($value['name'] !== '' && $this->values->findByName($value['name']) !== null) {
                $result->skipped[] = $value['name'];

                continue;
            }

            if ($value['name'] !== '' && isset($namesInFile[$nameKey])) {
                $result->errors[] = __('console.values_import.duplicate', ['line' => $lineNumber, 'name' => $value['name']]);

                continue;
            }

            $namesInFile[$nameKey] = true;

            $validator = Validator::make($value, [
                'name' => ['required', 'string', 'max:255'],
                'category' => ['nullable', 'string', 'max:255'],
                'high_examples' => ['array', 'min:1'],
                'low_examples' => ['array', 'min:1'],
                ...collect(self::LISTS)->mapWithKeys(fn (string $list) => [$list.'.*' => ['string', 'min:2', 'max:255']])->all(),
            ], attributes: collect(self::LISTS)->mapWithKeys(fn (string $list) => [
                $list => __('admin.values.fields.'.$list),
                $list.'.*' => __('admin.values.fields.'.$list),
            ])->all());

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $result->errors[] = __('console.values_import.row_error', ['line' => $lineNumber, 'message' => $message]);
                }

                continue;
            }

            $values[] = $value;
        }

        if ($result->errors !== [] || $dryRun) {
            $result->wouldImport = count($values);

            return $result;
        }

        DB::transaction(function () use ($values, $result): void {
            foreach ($values as $value) {
                $categoryId = null;

                if ($value['category'] !== '') {
                    $category = ValueCategory::query()->firstOrCreate(['name' => $value['category']]);
                    $categoryId = $category->id;

                    if ($category->wasRecentlyCreated) {
                        $result->createdCategories[] = $category->name;
                    }
                }

                $this->values->create([
                    'name' => $value['name'],
                    'value_category_id' => $categoryId,
                    ...array_intersect_key($value, array_flip(self::LISTS)),
                ]);

                $result->imported++;
            }
        });

        return $result;
    }

    /**
     * @param  list<string>  $headers
     * @return array<string, string> header => field
     */
    private function mapColumns(array $headers, ValueImportResult $result): array
    {
        $columns = [];

        foreach ($headers as $header) {
            $key = trim(preg_replace('/^[16]\s+/', '', Str::of($header)->ascii()->lower()->replaceMatches('/[^a-z0-9 ]+/', ' ')->squish()->toString()) ?? '');

            if (isset(self::COLUMNS[$key])) {
                $columns[$header] = self::COLUMNS[$key];
            } elseif ($header !== '') {
                $result->ignoredColumns[] = $header;
            }
        }

        return $columns;
    }

    /**
     * @param  array<string, string>  $row
     * @param  array<string, string>  $columns
     * @return array<string, mixed>
     */
    private function valueFromRow(array $row, array $columns): array
    {
        $value = ['name' => '', 'category' => '', ...array_fill_keys(self::LISTS, [])];

        foreach ($columns as $header => $field) {
            $cell = $row[$header] ?? '';

            $value[$field] = in_array($field, self::LISTS, true) ? $this->listItems($cell) : trim($cell);
        }

        return $value;
    }

    /**
     * One item per line; leading bullets are dropped.
     *
     * @return list<string>
     */
    private function listItems(string $cell): array
    {
        $lines = preg_split('/\R/u', $cell) ?: [];

        return array_values(array_filter(
            array_map(fn (string $line): string => trim(preg_replace('/^\s*(?:[-•*–]|\d+[.)])\s+/u', '', $line) ?? ''), $lines),
            fn (string $line): bool => $line !== '',
        ));
    }
}
