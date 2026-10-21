<?php

namespace App\Services\Capabilities;

use App\Enums\CapabilityLevel;
use App\Enums\CapabilityNature;
use App\Enums\CapabilityStatus;
use App\Models\Capability;
use App\Models\CapabilityGroup;
use App\Models\CapabilityImport;
use App\Models\Precondition;
use App\Services\Import\SpreadsheetReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * "Frissítés a mégis tárából" (07 · 7.6): reads the Képesség and the
 * Képesség-részletek sheet of the xlsx export, nothing else. Matches by
 * azonosító: a new one is a new capability (v1), a changed one a new version
 * with an automatic note, one missing from the file stays. The preview runs
 * the same load and rolls it back, so it shows exactly what the load does.
 */
class CapabilityImporter
{
    public const SHEET = 'Képesség';

    public const DETAILS_SHEET = 'Képesség-részletek';

    /**
     * Columns of the Képesség sheet, compared without case, accents and
     * punctuation. Any other column goes to the read-only details.
     */
    private const COLUMNS = [
        'azonosito' => 'code',
        'nev magyar' => 'name',
        'nev' => 'name',
        'nev angol' => 'name_en',
        'elsodleges szint' => 'level',
        'csoport' => 'group',
        'statusz' => 'status',
        'jelleg' => 'nature',
        'rovid definicio' => 'definition',
        'kimondhato forma' => 'statement',
        'mi nem ez' => 'non_examples',
        'atfedes' => 'overlaps',
        'szukseges elofeltetelek' => 'preconditions',
        'csabito tetel' => 'tempting',
        'szinonimak kulcsszavak' => 'keywords',
    ];

    /**
     * The columns every load needs: the required fields of the card (07 · 7.2).
     */
    private const REQUIRED_COLUMNS = ['code', 'name', 'group', 'level', 'status', 'definition'];

    /**
     * Counts of the Mintatár: they come with the patterns (NM-19), not here.
     */
    private const IGNORED_COLUMNS = ['mintak szama', 'megerositett talalatok', 'kapcsolt bizonyitekok'];

    /**
     * Fields of the Képesség-részletek sheet with a place on the card;
     * the "érettség N" rows are the steps, every other field is a detail.
     */
    private const DETAIL_FIELDS = [
        'kimondhato forma' => 'statement',
        'szinonimak kulcsszavak' => 'keywords',
        'megjelenes egyen' => 'appearance_individual',
        'megjelenes csapat' => 'appearance_team',
        'megjelenes szervezet' => 'appearance_organization',
        'megjelenes okoszisztema' => 'appearance_ecosystem',
    ];

    private const LEVELS = [
        'egyen' => CapabilityLevel::Individual,
        'csapat' => CapabilityLevel::Team,
        'szervezet' => CapabilityLevel::Organization,
        'okoszisztema' => CapabilityLevel::Ecosystem,
    ];

    private const STATUSES = [
        'hasznalt' => CapabilityStatus::Used,
        'jelolt' => CapabilityStatus::Candidate,
        'csak irodalom' => CapabilityStatus::Literature,
        'felretett' => CapabilityStatus::SetAside,
    ];

    private const NATURES = [
        'kepesseg' => CapabilityNature::Capability,
        'keszseg jellegu' => CapabilityNature::SkillLike,
        'kerdeses' => CapabilityNature::Questionable,
    ];

    public function __construct(
        private SpreadsheetReader $reader,
        private CapabilityService $capabilities,
    ) {}

    public function preview(string $path): CapabilityImportResult
    {
        return $this->run($path, null);
    }

    public function import(string $path, string $fileName): CapabilityImportResult
    {
        return $this->run($path, $fileName);
    }

    /**
     * @param  string|null  $fileName  null for the preview
     */
    private function run(string $path, ?string $fileName): CapabilityImportResult
    {
        $result = new CapabilityImportResult;
        $sheets = $this->reader->readSheets($path, [self::SHEET, self::DETAILS_SHEET]);

        if (! isset($sheets[self::SHEET])) {
            $result->errors[] = __('admin.capabilities.import.missing_sheet', ['sheet' => self::SHEET]);

            return $result;
        }

        $columns = $this->mapColumns($sheets[self::SHEET]['headers'], $result);

        $missing = array_diff(self::REQUIRED_COLUMNS, $columns);

        if ($missing !== []) {
            foreach ($missing as $field) {
                $result->errors[] = __('admin.capabilities.import.missing_column', ['field' => __('admin.capabilities.fields.'.$field)]);
            }

            return $result;
        }

        $sourced = array_values(array_unique(array_filter($columns, fn (string $field): bool => $field !== 'detail')));
        $entries = $this->entries($sheets[self::SHEET]['rows'], $columns, $result);

        if (isset($sheets[self::DETAILS_SHEET])) {
            $sourced = [...$sourced, ...$this->applyDetails($sheets[self::DETAILS_SHEET], $entries, $result)];
        }

        $entries = $this->validEntries($entries, $result);

        DB::beginTransaction();

        try {
            $this->write($entries, array_values(array_unique($sourced)), $result);

            if ($fileName === null) {
                DB::rollBack();
            } else {
                CapabilityImport::query()->create([
                    'file_name' => mb_substr($fileName, 0, 255),
                    'created_count' => count($result->created),
                    'updated_count' => count($result->updated),
                    'unchanged_count' => count($result->unchanged),
                    'skipped_count' => count($result->errors),
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
     * @return array<string, string> header => field, or "detail"
     */
    private function mapColumns(array $headers, CapabilityImportResult $result): array
    {
        $columns = [];

        foreach ($headers as $header) {
            $key = SheetCells::normalize($header);

            if ($key === '' || in_array($key, self::IGNORED_COLUMNS, true)) {
                if ($header !== '') {
                    $result->ignoredColumns[] = $header;
                }

                continue;
            }

            $field = self::COLUMNS[$key] ?? 'detail';

            // A second column for the same field is only a detail.
            $columns[$header] = $field !== 'detail' && in_array($field, $columns, true) ? 'detail' : $field;
        }

        return $columns;
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @param  array<string, string>  $columns
     * @return array<string, array<string, mixed>> by code
     */
    private function entries(array $rows, array $columns, CapabilityImportResult $result): array
    {
        $entries = [];
        $codeHeader = array_search('code', $columns, true);

        foreach ($rows as $line => $row) {
            $code = SheetCells::code($row[$codeHeader] ?? '');

            if ($code === '') {
                $result->errors[] = __('admin.capabilities.import.missing_code', ['line' => $line]);

                continue;
            }

            if (isset($entries[$code])) {
                $result->errors[] = __('admin.capabilities.import.duplicate_code', ['line' => $line, 'code' => $code]);

                continue;
            }

            $entry = ['line' => $line, 'code' => $code, 'attributes' => [], 'group' => '', 'links' => null, 'details' => [], 'proposed' => [], 'states' => [], 'errors' => []];

            foreach ($columns as $header => $field) {
                $this->applyCell($entry, $field, $header, $row[$header] ?? '', $result);
            }

            $entries[$code] = $entry;
        }

        return $entries;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function applyCell(array &$entry, string $field, string $header, string $raw, CapabilityImportResult $result): void
    {
        switch ($field) {
            case 'code':
                return;

            case 'detail':
                $this->addDetail($entry, $header, $raw);

                return;

            case 'non_examples':
            case 'overlaps':
            case 'keywords':
                $entry['attributes'][$field] = SheetCells::items($raw, $field, $entry);

                return;

            case 'group':
                $entry['group'] = SheetCells::text($raw, $field, $entry);

                return;

            case 'level':
            case 'status':
            case 'nature':
                $text = SheetCells::text($raw, $field, $entry);
                $options = ['level' => self::LEVELS, 'status' => self::STATUSES, 'nature' => self::NATURES][$field];
                $value = $options[SheetCells::normalize($text)] ?? null;

                if ($value === null && $text !== '') {
                    $entry['errors'][$field] = __('admin.capabilities.import.unknown_option', ['field' => __('admin.capabilities.fields.'.$field), 'value' => $text]);
                }

                $entry['attributes'][$field] = $value;

                return;

            case 'tempting':
                $text = SheetCells::text($raw, $field, $entry);
                $answer = Str::lower(Str::ascii($text));
                $entry['attributes']['tempting'] = match (true) {
                    str_starts_with($answer, 'igen') => true,
                    str_starts_with($answer, 'nem') => false,
                    default => null,
                };

                // The reasoning after the answer is kept as a detail.
                $remark = trim((string) preg_replace('/^(igen|nem)\b[\s,;:.–-]*/iu', '', $text));
                $remark = trim((string) preg_replace('/^\((.*?)\)?$/su', '$1', $remark));

                if ($entry['attributes']['tempting'] === null && $text !== '') {
                    $remark = $text;
                }

                $this->addDetail($entry, $header, $remark);

                return;

            case 'preconditions':
                $text = SheetCells::text($raw, $field, $entry);
                $entry['links'] = SheetCells::links($text);

                // What the codes and their notes do not say stays readable as a detail.
                $rest = SheetCells::linkRest($text);

                if ($rest !== '') {
                    $this->addDetail($entry, $header, $text);
                }

                return;

            default:
                $entry['attributes'][$field] = SheetCells::text($raw, $field, $entry);
        }
    }

    /**
     * Rows of the Képesség-részletek sheet: képesség | mező | érték.
     *
     * @param  array{headers: list<string>, rows: array<int, array<string, string>>}  $sheet
     * @param  array<string, array<string, mixed>>  $entries
     * @return list<string> the card fields the sheet is a source for
     */
    private function applyDetails(array $sheet, array &$entries, CapabilityImportResult $result): array
    {
        $headers = collect($sheet['headers'])->mapWithKeys(fn (string $header) => [SheetCells::normalize($header) => $header]);
        $capabilityHeader = $headers->get('kepesseg') ?? $sheet['headers'][0] ?? null;
        $fieldHeader = $headers->get('mezo') ?? $sheet['headers'][1] ?? null;
        $valueHeader = $headers->get('ertek') ?? $sheet['headers'][2] ?? null;

        if ($capabilityHeader === null || $fieldHeader === null || $valueHeader === null) {
            return [];
        }

        $byName = collect($entries)->mapWithKeys(fn (array $entry, string $code) => [mb_strtolower((string) ($entry['attributes']['name'] ?? '')) => $code]);
        $sourced = [];
        $unknown = [];

        foreach ($sheet['rows'] as $row) {
            $key = trim($row[$capabilityHeader] ?? '');
            $label = trim($row[$fieldHeader] ?? '');
            $code = isset($entries[SheetCells::code($key)]) ? SheetCells::code($key) : $byName->get(mb_strtolower($key));

            if ($label === '') {
                continue;
            }

            if ($code === null) {
                $unknown[$key] = true;

                continue;
            }

            $entry = &$entries[$code];
            $raw = $row[$valueHeader] ?? '';
            $normalized = SheetCells::normalize($label);

            if (preg_match('/^erettseg ([1-6])\b/', $normalized, $stage) === 1) {
                $field = 'stage_'.$stage[1];
            } else {
                $field = self::DETAIL_FIELDS[$normalized] ?? null;
            }

            if ($field === null) {
                $this->addDetail($entry, $label, $raw);
                unset($entry);

                continue;
            }

            $sourced[] = match (true) {
                str_starts_with($field, 'appearance_') => 'appearances',
                str_starts_with($field, 'stage_') => 'stages',
                default => $field,
            };

            if ($field === 'keywords') {
                $entry['attributes']['keywords'] = [...($entry['attributes']['keywords'] ?? []), ...SheetCells::items($raw, $field, $entry)];
            } elseif (str_starts_with($field, 'appearance_')) {
                $entry['attributes']['appearances'][substr($field, strlen('appearance_'))] = SheetCells::text($raw, $field, $entry);
            } elseif (str_starts_with($field, 'stage_')) {
                $entry['attributes']['stages'][substr($field, strlen('stage_'))] = SheetCells::text($raw, $field, $entry);
            } else {
                $entry['attributes'][$field] = SheetCells::text($raw, $field, $entry);
            }

            unset($entry);
        }

        foreach (array_keys($unknown) as $key) {
            $result->warnings[] = __('admin.capabilities.import.unknown_detail_capability', ['capability' => $key]);
        }

        return array_values(array_unique($sourced));
    }

    /**
     * @param  array<string, array<string, mixed>>  $entries
     * @return array<string, array<string, mixed>>
     */
    private function validEntries(array $entries, CapabilityImportResult $result): array
    {
        $names = [];

        foreach ($entries as $code => $entry) {
            $errors = $entry['errors'];
            $attributes = $entry['attributes'];

            foreach (['name', 'level', 'status', 'definition'] as $required) {
                if (blank($attributes[$required] ?? null) && ! isset($errors[$required])) {
                    $errors[$required] = __('admin.capabilities.import.required', ['field' => __('admin.capabilities.fields.'.$required)]);
                }
            }

            if ($entry['group'] === '') {
                $errors['group'] = __('admin.capabilities.import.required', ['field' => __('admin.capabilities.fields.group')]);
            }

            if (mb_strlen($code) > 20 || mb_strlen((string) ($attributes['name'] ?? '')) > 255 || mb_strlen((string) ($attributes['name_en'] ?? '')) > 255) {
                $errors[] = __('admin.capabilities.import.too_long');
            }

            $nameKey = mb_strtolower((string) ($attributes['name'] ?? ''));

            if ($nameKey !== '' && isset($names[$nameKey])) {
                $errors[] = __('admin.capabilities.import.duplicate_name', ['name' => $attributes['name']]);
            }

            if ($errors !== []) {
                foreach ($errors as $message) {
                    $result->errors[] = __('admin.capabilities.import.row_error', ['line' => $entry['line'], 'code' => $code, 'message' => $message]);
                }

                unset($entries[$code]);

                continue;
            }

            $names[$nameKey] = true;

            foreach (Capability::LISTS as $list) {
                foreach ($attributes[$list] ?? [] as $item) {
                    if (mb_strlen($item) > Capability::ITEM_MAX_LENGTH) {
                        $result->warnings[] = __('admin.capabilities.import.long_item', ['code' => $code, 'field' => __('admin.capabilities.fields.'.$list), 'max' => Capability::ITEM_MAX_LENGTH]);
                    }
                }
            }
        }

        return $entries;
    }

    /**
     * @param  array<string, array<string, mixed>>  $entries
     * @param  list<string>  $sourced  the card fields the file is a source for
     */
    private function write(array $entries, array $sourced, CapabilityImportResult $result): void
    {
        $note = __('admin.capabilities.import.version_note', ['date' => now()->format('Y.m.d.')]);
        $existing = Capability::query()->whereIn('code', array_keys($entries))->get()->keyBy('code');
        $preconditions = Precondition::query()->pluck('id', 'code');
        $saved = [];

        foreach ($entries as $code => $entry) {
            $capability = $existing->get($code);
            $name = $entry['attributes']['name'] ?? $capability?->name;

            if ($name !== null && $this->capabilities->findByName($name, $capability?->id) !== null) {
                $result->errors[] = __('admin.capabilities.import.row_error', ['line' => $entry['line'], 'code' => $code, 'message' => __('admin.capabilities.import.name_taken', ['name' => $name])]);

                continue;
            }

            $isNew = $capability === null;
            $previousStatus = $capability?->status;
            $capability ??= new Capability(['code' => $code, 'slug' => $this->capabilities->slugFor((string) $name)]);

            $capability->fill([
                ...$this->sourcedAttributes($entry, $sourced),
                'capability_group_id' => $this->group($entry['group'], $result)->id,
                'details' => $entry['details'],
                'proposed_fields' => $this->marks($capability->proposed_fields, $entry['proposed'], $sourced, list: true),
                'field_states' => $this->marks($capability->field_states, $entry['states'], $sourced, list: false),
            ]);

            $capability->save();

            if ($entry['links'] !== null) {
                $this->capabilities->syncPreconditions($capability, collect($entry['links'])
                    ->filter(fn (array $link) => $preconditions->has($link['code']))
                    ->mapWithKeys(fn (array $link) => [$preconditions->get($link['code']) => $link['note']])
                    ->all());
            }

            $saved[$code] = [$capability, $entry, $isNew, $previousStatus];
        }

        // Dependencies once every capability of the file is in, so ÚJ-01 can point at a later row.
        $codes = Capability::query()->pluck('id', 'code');

        foreach ($saved as $code => [$capability, $entry, $isNew, $previousStatus]) {
            if ($entry['links'] !== null) {
                $dependencies = [];

                foreach ($entry['links'] as $link) {
                    if (str_starts_with($link['code'], 'E')) {
                        continue;
                    }

                    if (! $codes->has($link['code'])) {
                        $result->warnings[] = __('admin.capabilities.import.unknown_dependency', ['code' => $code, 'dependency' => $link['code']]);

                        continue;
                    }

                    $dependencies[$codes->get($link['code'])] = $link['note'];
                }

                $this->capabilities->syncDependencies($capability, $dependencies);
            }

            $version = $capability->recordVersion($note);

            if ($version !== null || $capability->imported_at === null) {
                $capability->forceFill(['imported_at' => now()])->save();
            }

            match (true) {
                $isNew => $result->created[] = ['code' => $code, 'name' => $capability->name],
                $version !== null => $result->updated[] = ['code' => $code, 'name' => $capability->name, 'fields' => $version->changed_fields],
                default => $result->unchanged[] = $code,
            };

            // "félretett" comes in archived (07 · 7.5); back from it, restored.
            if ($capability->status === CapabilityStatus::SetAside && ! $capability->isArchived()) {
                $this->capabilities->archive($capability);
                $result->archived[] = $code;
            } elseif ($previousStatus === CapabilityStatus::SetAside && $capability->status !== CapabilityStatus::SetAside && $capability->isArchived()) {
                $this->capabilities->restore($capability);
                $result->restored[] = $code;
            }
        }
    }

    /**
     * The attributes the file is a source for; a field without a source
     * (e.g. the keywords) keeps what the admin wrote.
     *
     * @param  array<string, mixed>  $entry
     * @param  list<string>  $sourced
     * @return array<string, mixed>
     */
    private function sourcedAttributes(array $entry, array $sourced): array
    {
        $attributes = [];

        foreach (['name', 'name_en', 'level', 'status', 'nature', 'tempting', 'definition', 'statement', 'non_examples', 'overlaps', 'keywords', 'appearances', 'stages'] as $field) {
            if (! in_array($field, $sourced, true)) {
                continue;
            }

            $value = $entry['attributes'][$field] ?? (in_array($field, ['non_examples', 'overlaps', 'keywords', 'appearances', 'stages'], true) ? [] : null);
            $attributes[$field] = is_string($value) && $value === '' ? null : $value;
        }

        if (isset($attributes['appearances'])) {
            $attributes['appearances'] = array_filter($attributes['appearances'], fn (?string $text): bool => filled($text));
        }

        if (isset($attributes['stages'])) {
            $attributes['stages'] = array_filter($attributes['stages'], fn (?string $text): bool => filled($text));
        }

        return $attributes;
    }

    /**
     * The "[JAVASLAT]" marks or empty states: the file's for the fields it is
     * a source for, the earlier ones for the rest.
     *
     * @param  array<int|string, string>  $current
     * @param  array<int|string, string>  $fromFile
     * @param  list<string>  $sourced
     * @return array<int|string, string>
     */
    private function marks(array $current, array $fromFile, array $sourced, bool $list): array
    {
        $isSourced = fn (string $field): bool => in_array(match (true) {
            str_starts_with($field, 'appearance_') => 'appearances',
            str_starts_with($field, 'stage_') => 'stages',
            default => $field,
        }, $sourced, true);

        if ($list) {
            return array_values(array_unique([...array_filter($current, fn (string $field) => ! $isSourced($field)), ...$fromFile]));
        }

        return [...array_filter($current, fn (string $state, string $field) => ! $isSourced($field), ARRAY_FILTER_USE_BOTH), ...$fromFile];
    }

    /**
     * By the English name of the Sheet or the Hungarian one; a group the
     * Sheet starts using comes in with its English name (07, question 3).
     */
    private function group(string $name, CapabilityImportResult $result): CapabilityGroup
    {
        $key = Str::of($name)->lower()->squish()->toString();
        $group = CapabilityGroup::query()
            ->whereRaw('lower(name_en) = ?', [$key])
            ->orWhereRaw('lower(name) = ?', [$key])
            ->first();

        if ($group !== null) {
            return $group;
        }

        $result->createdGroups[] = $name;

        return CapabilityGroup::query()->create(['name' => $name, 'name_en' => $name]);
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function addDetail(array &$entry, string $label, string $raw): void
    {
        $value = trim($raw);

        if ($value === '' || SheetCells::emptyState($value) !== null) {
            return;
        }

        foreach ($entry['details'] as $index => $detail) {
            if ($detail['label'] === $label) {
                $entry['details'][$index]['value'] .= "\n".$value;

                return;
            }
        }

        $entry['details'][] = ['label' => $label, 'value' => $value];
    }
}
