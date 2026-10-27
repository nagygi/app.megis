<?php

namespace App\Services\Values;

use App\Enums\ValueGrade;
use App\Enums\ValueOrigin;
use App\Enums\ValueSvsStatus;
use App\Models\Value;
use App\Models\ValueCategory;
use App\Models\ValueImport;
use App\Services\Import\SpreadsheetReader;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

/**
 * Loads and updates the Értéktár from the mégis table (03 · 3.6): one row
 * per value, one column per field of the value card, list items on separate
 * lines of a cell or separated by " · ". A value is matched by its code, then
 * by its name; a change is a new version with a note. The file is the source
 * only for the columns it has. When any row is invalid, nothing is loaded.
 * The dry run makes the same load and rolls it back, so the preview of the
 * Érték Import shows exactly what the load does.
 */
class ValueImporter
{
    /**
     * Accepted headers, compared without case, punctuation and the 1 / 6 prefix:
     * the Hungarian ones of the sample file and the ones of the mégis table.
     */
    private const COLUMNS = [
        'id' => 'code',
        'kod' => 'code',
        'azonosito' => 'code',
        'nev' => 'name',
        'name hu' => 'name',
        'nev magyar' => 'name',
        'name en' => 'name_en',
        'nev angol' => 'name_en',
        'kategoria' => 'category',
        'category hu' => 'category',
        'kategoria magyar' => 'category',
        'category en' => 'category_en',
        'kategoria angol' => 'category_en',
        'higher order' => 'higher_order',
        'felsobb szint' => 'higher_order',
        'definicio' => 'definition',
        'definition hu' => 'definition',
        'definicio magyar' => 'definition',
        'definition en' => 'definition_en',
        'definicio angol' => 'definition_en',
        'mit jelent' => 'meanings',
        'mit nem jelent' => 'non_meanings',
        'hamis pozitivok' => 'false_positives',
        'atfedesek' => 'overlaps',
        'atfedesek mas ertekekkel' => 'overlaps',
        'mikor teljesul nagyon' => 'high_examples',
        'mikor nagyon nem teljesul' => 'low_examples',
        'peldak' => 'examples',
        'pelda' => 'examples',
        'szinonimak kulcsszavak' => 'keywords',
        'szinonimak' => 'keywords',
        'szakirodalom' => 'literature',
        'szakirodalom meresi eszkozok' => 'literature',
        'alatamasztottsag' => 'evidence',
        'szakirodalmi alatamasztottsag' => 'evidence',
        'transzponalhatosag' => 'transposability',
        'svs index' => 'svs_status',
    ];

    /**
     * The scale of the literature support and of the transposability, by the normalized label.
     */
    private const GRADES = [
        'eros' => ValueGrade::Strong,
        'kozepes' => ValueGrade::Medium,
        'gyenge' => ValueGrade::Weak,
        'strong' => ValueGrade::Strong,
        'medium' => ValueGrade::Medium,
        'weak' => ValueGrade::Weak,
    ];

    private const SVS_STATUSES = [
        'igen' => ValueSvsStatus::Yes,
        'nem x kodolt' => ValueSvsStatus::XCoded,
        'nem ellenorizendo' => ValueSvsStatus::ToVerify,
        'yes' => ValueSvsStatus::Yes,
        'x coded' => ValueSvsStatus::XCoded,
        'to verify' => ValueSvsStatus::ToVerify,
    ];

    private const TEXTS = ['code', 'name', 'name_en', 'category', 'category_en', 'higher_order', 'definition', 'definition_en', 'evidence', 'transposability', 'svs_status'];

    public function __construct(
        private SpreadsheetReader $reader,
        private ValueService $values,
    ) {}

    /**
     * @param  string|null  $fileName  the name the admin uploaded, for the note and the log
     */
    public function import(string $path, bool $dryRun = false, ?string $fileName = null): ValueImportResult
    {
        $sheet = $this->reader->read($path);
        $result = new ValueImportResult;

        $columns = $this->mapColumns($sheet['headers'], $result);

        if (! in_array('name', $columns, true)) {
            $result->errors[] = __('console.values_import.missing_name_column');

            return $result;
        }

        $sourced = array_values(array_unique($columns));
        $entries = $this->entries($sheet['rows'], $columns, $result);
        $categories = $this->categories($entries, $result);
        $targets = $this->targets($entries, $result);

        if ($result->failed()) {
            return $result;
        }

        $fileName ??= basename($path);
        $note = mb_substr(__('console.values_import.version_note', ['file' => $fileName, 'date' => now()->format('Y.m.d.')]), 0, 500);

        DB::beginTransaction();

        try {
            $this->write($entries, $targets, $this->saveCategories($categories, $sourced, $result), $sourced, $note, $result);

            if ($dryRun) {
                DB::rollBack();
            } else {
                ValueImport::query()->create([
                    'file_name' => mb_substr($fileName, 0, 255),
                    'created_count' => count($result->created),
                    'updated_count' => count($result->updated),
                    'unchanged_count' => count($result->unchanged),
                    'categories_created_count' => count($result->createdCategories),
                    'created_by' => auth()->id(),
                ]);
                DB::commit();
            }
        } catch (Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }

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
            $field = self::COLUMNS[trim((string) preg_replace('/^[16]\s+/', '', self::normalize($header)))] ?? null;

            if ($field !== null && ! in_array($field, $columns, true)) {
                $columns[$header] = $field;
            } elseif ($header !== '') {
                $result->ignoredColumns[] = $header;
            }
        }

        return $columns;
    }

    /**
     * The valid rows by line number; the invalid ones become errors.
     *
     * @param  array<int, array<string, string>>  $rows
     * @param  array<string, string>  $columns
     * @return array<int, array<string, mixed>>
     */
    private function entries(array $rows, array $columns, ValueImportResult $result): array
    {
        $entries = [];
        $names = [];
        $codes = [];

        foreach ($rows as $line => $row) {
            $entry = $this->entryFromRow($row, $columns);
            $nameKey = mb_strtolower($entry['name']);

            if ($entry['name'] !== '' && isset($names[$nameKey])) {
                $result->errors[] = __('console.values_import.duplicate', ['line' => $line, 'name' => $entry['name']]);

                continue;
            }

            if ($entry['code'] !== '' && isset($codes[$entry['code']])) {
                $result->errors[] = __('console.values_import.duplicate_code', ['line' => $line, 'code' => $entry['code']]);

                continue;
            }

            $names[$nameKey] = true;
            $codes[$entry['code']] = true;

            $validator = Validator::make($entry, [
                'code' => ['nullable', 'string', 'max:20'],
                'name' => ['required', 'string', 'max:255'],
                'name_en' => ['nullable', 'string', 'max:255'],
                'category' => ['nullable', 'string', 'max:255'],
                'category_en' => ['nullable', 'string', 'max:255'],
                'higher_order' => ['nullable', 'string', 'max:255'],
                'definition' => ['nullable', 'string', 'max:1000'],
                'definition_en' => ['nullable', 'string', 'max:1000'],
                'high_examples' => ['array', 'min:1'],
                'low_examples' => ['array', 'min:1'],
                ...collect(Value::LISTS)->mapWithKeys(fn (string $list) => [$list.'.*' => ['string', 'min:2', 'max:'.Value::itemMaxLength($list)]])->all(),
            ], attributes: collect([...self::TEXTS, ...Value::LISTS])->mapWithKeys(fn (string $field) => [
                $field => self::label($field),
                $field.'.*' => self::label($field),
            ])->all());

            $errors = $validator->errors()->all();

            foreach (['evidence' => self::GRADES, 'transposability' => self::GRADES, 'svs_status' => self::SVS_STATUSES] as $field => $options) {
                if ($entry[$field] === '') {
                    $entry[$field] = null;
                } elseif (isset($options[self::normalize($entry[$field])])) {
                    $entry[$field] = $options[self::normalize($entry[$field])];
                } else {
                    $errors[] = __('console.values_import.unknown_option', ['field' => self::label($field), 'value' => $entry[$field]]);
                }
            }

            foreach ($errors as $message) {
                $result->errors[] = __('console.values_import.row_error', ['line' => $line, 'message' => $message]);
            }

            if ($errors === []) {
                $entries[$line] = $entry;
            }
        }

        return $entries;
    }

    /**
     * @param  array<string, string>  $row
     * @param  array<string, string>  $columns
     * @return array<string, mixed>
     */
    private function entryFromRow(array $row, array $columns): array
    {
        $entry = [...array_fill_keys(self::TEXTS, ''), ...array_fill_keys(Value::LISTS, [])];

        foreach ($columns as $header => $field) {
            $cell = $row[$header] ?? '';

            $entry[$field] = in_array($field, Value::LISTS, true) ? $this->listItems($cell) : trim($cell);
        }

        $entry['code'] = self::code($entry['code']);

        return $entry;
    }

    /**
     * One category per Hungarian name, with the English name and the higher
     * order value the file gives it; two rows may not disagree on them.
     *
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<string, array{name: string, name_en: string, higher_order: string, line: int}> by the lower-case name
     */
    private function categories(array $entries, ValueImportResult $result): array
    {
        $categories = [];

        foreach ($entries as $line => $entry) {
            if ($entry['category'] === '') {
                if ($entry['category_en'] !== '' || $entry['higher_order'] !== '') {
                    $result->errors[] = __('console.values_import.row_error', ['line' => $line, 'message' => __('console.values_import.category_without_name')]);
                }

                continue;
            }

            $key = mb_strtolower($entry['category']);
            $categories[$key] ??= ['name' => $entry['category'], 'name_en' => '', 'higher_order' => '', 'line' => $line];

            foreach (['name_en' => 'category_en', 'higher_order' => 'higher_order'] as $field => $column) {
                if ($entry[$column] === '') {
                    continue;
                }

                if ($categories[$key][$field] === '') {
                    $categories[$key][$field] = $entry[$column];
                } elseif ($categories[$key][$field] !== $entry[$column]) {
                    $result->errors[] = __('console.values_import.row_error', ['line' => $line, 'message' => __('console.values_import.category_conflict', [
                        'name' => $entry['category'],
                        'field' => self::label($column),
                        'first' => $categories[$key]['line'],
                    ])]);
                }
            }
        }

        return $categories;
    }

    /**
     * The value each row updates, by code, then by name; null for a new value.
     *
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<int, Value|null> by line number
     */
    private function targets(array $entries, ValueImportResult $result): array
    {
        $values = Value::query()->get();
        $byCode = $values->whereNotNull('code')->keyBy('code');
        $byName = $values->keyBy(fn (Value $value): string => mb_strtolower($value->name));
        $targets = [];
        $claimed = [];

        foreach ($entries as $line => $entry) {
            $nameKey = mb_strtolower($entry['name']);
            $value = $entry['code'] === '' ? null : $byCode->get($entry['code']);
            $error = null;

            if ($value !== null) {
                $named = $byName->get($nameKey);

                if ($named !== null && $named->isNot($value)) {
                    $error = __('console.values_import.name_taken', ['name' => $entry['name'], 'code' => $named->code ?? '–']);
                }
            } else {
                $value = $byName->get($nameKey);

                if ($value !== null && $value->code !== null && $entry['code'] !== '') {
                    $error = __('console.values_import.code_conflict', ['name' => $value->name, 'existing' => $value->code, 'code' => $entry['code']]);
                }
            }

            if ($error === null && $value !== null && isset($claimed[$value->id])) {
                $error = __('console.values_import.same_target', ['name' => $value->name, 'first' => $claimed[$value->id]]);
            }

            if ($error !== null) {
                $result->errors[] = __('console.values_import.row_error', ['line' => $line, 'message' => $error]);

                continue;
            }

            if ($value !== null) {
                $claimed[$value->id] = $line;
            }

            $targets[$line] = $value;
        }

        return $targets;
    }

    /**
     * Creates the new categories and updates the English name and higher
     * order value of the others, where the file has those columns.
     *
     * @param  array<string, array{name: string, name_en: string, higher_order: string, line: int}>  $categories
     * @param  list<string>  $sourced
     * @return array<string, int> category id by the lower-case name
     */
    private function saveCategories(array $categories, array $sourced, ValueImportResult $result): array
    {
        /** @var Collection<string, ValueCategory> $existing */
        $existing = ValueCategory::query()->get()->keyBy(fn (ValueCategory $category): string => mb_strtolower($category->name));
        $ids = [];

        foreach ($categories as $key => $category) {
            $attributes = [];

            foreach (['name_en' => 'category_en', 'higher_order' => 'higher_order'] as $field => $column) {
                if (in_array($column, $sourced, true)) {
                    $attributes[$field] = $category[$field] === '' ? null : $category[$field];
                }
            }

            $model = $existing->get($key);

            if ($model === null) {
                $model = ValueCategory::query()->create(['name' => $category['name'], ...$attributes]);
                $result->createdCategories[] = $model->label();
            } elseif ($model->fill($attributes)->isDirty()) {
                $model->save();
                $result->updatedCategories[] = $model->label();
            }

            $ids[$key] = $model->id;
        }

        return $ids;
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     * @param  array<int, Value|null>  $targets
     * @param  array<string, int>  $categoryIds
     * @param  list<string>  $sourced
     */
    private function write(array $entries, array $targets, array $categoryIds, array $sourced, string $note, ValueImportResult $result): void
    {
        foreach ($entries as $line => $entry) {
            $attributes = $this->attributes($entry, $sourced, $categoryIds);
            $value = $targets[$line];

            if ($value === null) {
                $result->created[] = $this->values->create($attributes, ValueOrigin::Admin, null, $note)->name;

                continue;
            }

            $version = $this->values->update($value, $attributes, $note);

            if ($version === null) {
                $result->unchanged[] = $value->name;
            } else {
                $result->updated[] = ['name' => $value->name, 'fields' => $version->changed_fields];
            }

            // The file is curated content: a value it matches is reviewed (03 · 3.7).
            if (! $value->isReviewed()) {
                $this->values->markReviewed($value);
                $result->reviewed[] = $value->name;
            }

            if ($value->isArchived()) {
                $result->warnings[] = __('console.values_import.archived_updated', ['line' => $line, 'name' => $value->name]);
            }
        }
    }

    /**
     * The attributes the file is a source for; a field without a column keeps
     * what the admin wrote, and a code is never taken away.
     *
     * @param  array<string, mixed>  $entry
     * @param  list<string>  $sourced
     * @param  array<string, int>  $categoryIds
     * @return array<string, mixed>
     */
    private function attributes(array $entry, array $sourced, array $categoryIds): array
    {
        $attributes = ['name' => $entry['name']];

        if ($entry['code'] !== '') {
            $attributes['code'] = $entry['code'];
        }

        if (in_array('category', $sourced, true)) {
            $attributes['value_category_id'] = $entry['category'] === '' ? null : $categoryIds[mb_strtolower($entry['category'])];
        }

        foreach (['name_en', 'definition', 'definition_en', 'evidence', 'transposability', 'svs_status', ...Value::LISTS] as $field) {
            if (in_array($field, $sourced, true)) {
                $attributes[$field] = $entry[$field] === '' ? null : $entry[$field];
            }
        }

        return $attributes;
    }

    /**
     * One item per line or per " · "; leading bullets and repeated items are dropped.
     *
     * @return list<string>
     */
    private function listItems(string $cell): array
    {
        $items = preg_split('/\R|\h+·\h+/u', $cell) ?: [];

        return array_values(array_unique(array_filter(
            array_map(fn (string $item): string => trim(preg_replace('/^\s*(?:[-•*–]|\d+[.)])\s+/u', '', $item) ?? ''), $items),
            fn (string $item): bool => $item !== '',
        )));
    }

    /**
     * "7.0" from a number cell is code 7; leading zeros stay.
     */
    private static function code(string $code): string
    {
        return (string) preg_replace('/^(\d+)[.,]0+$/', '$1', trim($code));
    }

    private static function label(string $field): string
    {
        return match ($field) {
            'category_en' => __('admin.value_categories.fields.name_en'),
            'higher_order' => __('admin.value_categories.fields.higher_order'),
            default => __('admin.values.fields.'.$field),
        };
    }

    private static function normalize(string $text): string
    {
        return Str::of($text)->ascii()->lower()->replaceMatches('/[^a-z0-9 ]+/', ' ')->squish()->toString();
    }
}
