<?php

namespace App\Services\Capabilities;

use App\Enums\CapabilityLevel;
use App\Enums\LevelMarker;
use App\Enums\PatternFit;
use App\Enums\PatternMaturity;
use App\Enums\PatternOrigin;
use App\Enums\PatternProduct;
use App\Enums\PatternSubtype;
use App\Enums\PatternType;
use App\Enums\PatternValidity;
use App\Models\Capability;
use App\Models\Pattern;
use App\Models\Precondition;

/**
 * The Minta sheet of the mégis tár (NM-19), loaded inside the transaction of
 * the capability load, after the capabilities. Matches by minta-azonosító
 * (M-xxx): a new one is v1, a changed one a new version, one missing from
 * the file stays. The counters the Sheet computes from its Találat and
 * Kapcsolat sheets come in as numbers; the case data itself never does.
 */
class PatternImporter
{
    public const SHEET = 'Minta';

    /**
     * Columns of the Minta sheet, compared without case, accents and punctuation.
     */
    private const COLUMNS = [
        'minta azonosito' => 'code',
        'tipus' => 'type',
        'altipus' => 'subtype',
        'nev' => 'name',
        'kapcsolt kepesseg ek' => 'capabilities',
        'minta erettseg' => 'sheet_maturity',
        'illeszkedes' => 'fit',
        'elofeltetel' => 'preconditions',
        'szint' => 'level',
        'jeloles' => 'marker',
        'kivulrol lathato' => 'externally_visible',
        'termekek' => 'products',
        'helyzet' => 'situation',
        'megfigyelheto viselkedes' => 'behavior',
        'mechanizmus' => 'mechanism',
        'feltetelek' => 'conditions',
        'latszat' => 'appearance',
        'kovetkezmeny' => 'consequence',
        'mi nem ez' => 'non_examples',
        'alternativ magyarazat' => 'alternative_explanation',
        'ellenproba' => 'counter_test',
        'hol latszik' => 'where_visible',
        'minimalis nyom' => 'minimal_trace',
        'hogyan mondjuk ki' => 'statement',
        'kovetkezo kerdes' => 'next_question',
        'fejlesztesi irany vagy ellenszer' => 'remedy',
        'eredet' => 'origin',
        'kulso forras' => 'external_source',
        'forrasszint' => 'source_level',
        'ero' => 'strength',
        'icp atvihetoseg' => 'icp_transferability',
        'felvette' => 'recorded_by',
        'datum' => 'recorded_on',
        'jovahagyta' => 'approved_by',
        'utolso felulvizsgalat' => 'reviewed_on',
        'ervenyes' => 'validity',
        'okoszisztema irany' => 'ecosystem_direction',
        'hivatkozas ellenorzes' => 'reference_check',
        'jovahagyott ugyfelek' => 'sheet_approved_clients',
        'javasolt talalatok' => 'sheet_proposed_findings',
        'teves riasztasok' => 'sheet_false_alarms',
        'bizonyitekok szama' => 'sheet_evidence_count',
    ];

    private const REQUIRED_COLUMNS = ['code', 'type', 'name'];

    private const COUNTERS = ['sheet_approved_clients', 'sheet_proposed_findings', 'sheet_false_alarms', 'sheet_evidence_count'];

    private const TYPES = [
        'kepesseg minta' => PatternType::Capability,
        'elofeltetel minta' => PatternType::Precondition,
        'detektor hibaminta' => PatternType::DetectorError,
    ];

    private const SUBTYPES = [
        'jelenlet jel' => PatternSubtype::Presence,
        'hiany jel' => PatternSubtype::Absence,
        'anti minta' => PatternSubtype::AntiPattern,
        'hatareset' => PatternSubtype::Borderline,
    ];

    private const LEVELS = [
        'egyen' => CapabilityLevel::Individual,
        'csapat' => CapabilityLevel::Team,
        'szervezet' => CapabilityLevel::Organization,
        'okoszisztema' => CapabilityLevel::Ecosystem,
    ];

    private const VALIDITIES = [
        'igen' => PatternValidity::Valid,
        'elavult' => PatternValidity::Obsolete,
        'elvetett' => PatternValidity::Rejected,
    ];

    private const ORIGINS = [
        'sajat eset' => PatternOrigin::OwnCase,
        'kulso forras' => PatternOrigin::External,
        'mindketto' => PatternOrigin::Both,
    ];

    private const FITS = [
        'pontos' => PatternFit::Exact,
        'reszleges' => PatternFit::Partial,
    ];

    private const PRODUCTS = [
        'teljes' => PatternProduct::Full,
        'snapshot belso' => PatternProduct::SnapshotInternal,
        'snapshot kulso' => PatternProduct::SnapshotExternal,
        'ertekmeres' => PatternProduct::ValueMeasurement,
        'bridge' => PatternProduct::Bridge,
        'os' => PatternProduct::OperatingSystem,
        'tukor' => PatternProduct::Mirror,
        'tesztpad' => PatternProduct::Testpad,
        'detektor' => PatternProduct::Detector,
    ];

    /**
     * Reads the sheet and writes it; the caller holds the transaction.
     *
     * @param  array{headers: list<string>, rows: array<int, array<string, string>>}  $sheet
     */
    public function load(array $sheet, CapabilityImportResult $result): void
    {
        $columns = $this->mapColumns($sheet['headers'], $result);
        $missing = array_diff(self::REQUIRED_COLUMNS, $columns);

        if ($missing !== []) {
            foreach ($missing as $field) {
                $result->errors[] = __('admin.capabilities.import.pattern_missing_column', ['field' => __('admin.patterns.fields.'.$field)]);
            }

            return;
        }

        $this->write($this->entries($sheet['rows'], $columns, $result), $result);
    }

    /**
     * @param  list<string>  $headers
     * @return array<string, string> header => field
     */
    private function mapColumns(array $headers, CapabilityImportResult $result): array
    {
        $columns = [];

        foreach ($headers as $header) {
            $field = self::COLUMNS[SheetCells::normalize($header)] ?? null;

            // A legacy column such as "hányszor láttuk (v0.3.2, kézi)" holds case counts: it stays out.
            if ($field === null || in_array($field, $columns, true)) {
                if ($header !== '') {
                    $result->ignoredColumns[] = self::SHEET.' · '.$header;
                }

                continue;
            }

            $columns[$header] = $field;
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
            $code = mb_strtoupper(trim($row[$codeHeader] ?? ''));

            if (preg_match('/^M-\d{3,4}$/', $code) !== 1) {
                $result->errors[] = $code === ''
                    ? __('admin.capabilities.import.pattern_missing_code', ['line' => $line])
                    : __('admin.capabilities.import.pattern_temporary_code', ['line' => $line, 'code' => $code]);

                continue;
            }

            if (isset($entries[$code])) {
                $result->errors[] = __('admin.capabilities.import.pattern_duplicate_code', ['line' => $line, 'code' => $code]);

                continue;
            }

            $entry = [
                'line' => $line, 'code' => $code, 'attributes' => [], 'content' => [], 'counters' => [],
                'capabilities' => [], 'fits' => null, 'preconditions' => [], 'proposed' => [], 'states' => [], 'errors' => [],
            ];

            foreach ($columns as $header => $field) {
                $this->applyCell($entry, $field, $row[$header] ?? '', $result);
            }

            $this->applyFits($entry, $result);

            if (($entry['attributes']['type'] ?? null) === null && ! isset($entry['errors']['type'])) {
                $entry['errors']['type'] = __('admin.capabilities.import.required', ['field' => __('admin.patterns.fields.type')]);
            }

            if (blank($entry['attributes']['name'] ?? null)) {
                $entry['errors']['name'] = __('admin.capabilities.import.required', ['field' => __('admin.patterns.fields.name')]);
            }

            if ($entry['errors'] !== []) {
                foreach ($entry['errors'] as $message) {
                    $result->errors[] = __('admin.capabilities.import.pattern_row_error', ['line' => $line, 'code' => $code, 'message' => $message]);
                }

                continue;
            }

            $entries[$code] = $entry;
        }

        return $entries;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function applyCell(array &$entry, string $field, string $raw, CapabilityImportResult $result): void
    {
        switch ($field) {
            case 'code':
                return;

            case 'name':
                $entry['attributes']['name'] = mb_substr(SheetCells::text($raw, $field, $entry), 0, 255);

                return;

            case 'type':
                $text = SheetCells::text($raw, $field, $entry);
                $entry['attributes']['type'] = self::TYPES[SheetCells::normalize($text)] ?? null;

                if ($entry['attributes']['type'] === null && $text !== '') {
                    $entry['errors']['type'] = __('admin.capabilities.import.unknown_option', ['field' => __('admin.patterns.fields.type'), 'value' => $text]);
                }

                return;

            case 'subtype':
            case 'level':
            case 'validity':
            case 'origin':
                $text = SheetCells::text($raw, $field, $entry);
                $options = ['subtype' => self::SUBTYPES, 'level' => self::LEVELS, 'validity' => self::VALIDITIES, 'origin' => self::ORIGINS][$field];
                $value = $options[SheetCells::normalize($text)] ?? null;

                if ($value === null && $text !== '') {
                    $result->warnings[] = __('admin.capabilities.import.pattern_unknown_option', ['code' => $entry['code'], 'field' => __('admin.patterns.fields.'.$field), 'value' => $text]);
                }

                $entry['attributes'][$field] = $value;

                return;

            case 'marker':
                $text = SheetCells::text($raw, $field, $entry);
                $key = SheetCells::normalize($text);
                $entry['attributes']['level_marker'] = match (true) {
                    str_starts_with($key, 'hatarfelulet') => LevelMarker::Boundary,
                    str_starts_with($key, 'mikro') => LevelMarker::Micro,
                    default => null,
                };

                // What the marker says beyond its kind, e.g. "(vezetői kör)".
                $note = trim((string) preg_replace('/^(határfelület|mikro szervezet)\s*/iu', '', $text));
                $entry['content']['marker_note'] = trim((string) preg_replace('/^\((.*)\)$/su', '$1', $note));

                if ($entry['attributes']['level_marker'] === null && $text !== '') {
                    $entry['content']['marker_note'] = $text;
                }

                return;

            case 'externally_visible':
                $text = SheetCells::text($raw, $field, $entry);
                $answer = SheetCells::normalize($text);
                $entry['attributes']['externally_visible'] = match (true) {
                    str_starts_with($answer, 'igen') => true,
                    str_starts_with($answer, 'nem') => false,
                    default => null,
                };
                $remark = trim((string) preg_replace('/^(igen|nem)\b[\s,;:.–-]*/iu', '', $text));
                $entry['content']['externally_visible_note'] = trim((string) preg_replace('/^\((.*)\)$/su', '$1', $remark));

                return;

            case 'products':
                $products = [];

                foreach (SheetCells::items($raw, $field, $entry) as $item) {
                    $key = SheetCells::normalize($item);
                    $product = self::PRODUCTS[$key] ?? (str_starts_with($key, 'detektor') ? PatternProduct::Detector : null);

                    if ($product === null) {
                        $result->warnings[] = __('admin.capabilities.import.pattern_unknown_option', ['code' => $entry['code'], 'field' => __('admin.patterns.fields.products'), 'value' => $item]);

                        continue;
                    }

                    $products[] = $product->value;
                }

                $entry['attributes']['products'] = array_values(array_unique($products));

                return;

            case 'where_visible':
                $entry['attributes']['where_visible'] = SheetCells::items($raw, $field, $entry);

                return;

            case 'capabilities':
                $entry['capabilities'] = array_values(array_filter(
                    array_column(SheetCells::links(SheetCells::text($raw, $field, $entry)), 'code'),
                    fn (string $code): bool => ! str_starts_with($code, 'E'),
                ));

                return;

            case 'fit':
                $entry['fits'] = SheetCells::text($raw, $field, $entry);

                return;

            case 'preconditions':
                $text = SheetCells::text($raw, $field, $entry);

                foreach (SheetCells::links($text) as $link) {
                    if (str_starts_with($link['code'], 'E')) {
                        $entry['preconditions'][$link['code']] = $link['note'];
                    }
                }

                // "nincs E-kód (akadálytípus: …)" and other text without a code.
                $entry['attributes']['precondition_note'] = $entry['preconditions'] === [] ? $text : SheetCells::linkRest($text);

                return;

            case 'sheet_maturity':
                $text = trim($raw);
                $entry['sheet_maturity'] = collect(PatternMaturity::cases())
                    ->first(fn (PatternMaturity $maturity): bool => SheetCells::normalize($maturity->label()) === SheetCells::normalize($text));

                return;

            case 'sheet_approved_clients':
            case 'sheet_proposed_findings':
            case 'sheet_false_alarms':
            case 'sheet_evidence_count':
                $entry['counters'][$field] = is_numeric(trim($raw)) ? max(0, (int) round((float) trim($raw))) : 0;

                return;

            default:
                $entry['content'][$field] = SheetCells::text($raw, $field, $entry);
        }
    }

    /**
     * "illeszkedés": one word for every linked capability, or one line per
     * capability ("RC-46: pontos", "RC-36: pontos (szervezet)").
     *
     * @param  array<string, mixed>  $entry
     */
    private function applyFits(array &$entry, CapabilityImportResult $result): void
    {
        $fits = [];
        $text = trim((string) $entry['fits']);

        if ($text !== '' && preg_match_all('/^\s*(?<code>RC-\d{2,3}|[ÚU]J-\d{2,3})\s*:\s*(?<fit>.+?)\s*$/mu', $text, $lines, PREG_SET_ORDER) > 0) {
            foreach ($lines as $line) {
                $fits[SheetCells::code($line['code'])] = $this->fit($line['fit'], $entry['code'], $result);
            }
        } elseif ($text !== '') {
            $fit = $this->fit($text, $entry['code'], $result);

            foreach ($entry['capabilities'] as $code) {
                $fits[$code] = $fit;
            }
        }

        foreach (array_keys($fits) as $code) {
            if (! in_array($code, $entry['capabilities'], true)) {
                $entry['capabilities'][] = $code;
                $result->warnings[] = __('admin.capabilities.import.pattern_fit_only', ['code' => $entry['code'], 'capability' => $code]);
            }
        }

        $entry['fits'] = $fits;
    }

    /**
     * @return array{fit: PatternFit|null, note: string|null}
     */
    private function fit(string $text, string $code, CapabilityImportResult $result): array
    {
        $word = SheetCells::normalize((string) strtok(trim($text), " (\n"));
        $fit = self::FITS[$word] ?? null;
        $note = $fit === null ? trim($text) : trim((string) preg_replace('/^\S+\s*/u', '', trim($text)));
        $note = trim((string) preg_replace('/^\((.*)\)$/su', '$1', $note));

        if ($fit === null) {
            $result->warnings[] = __('admin.capabilities.import.pattern_unknown_option', ['code' => $code, 'field' => __('admin.patterns.fields.fit'), 'value' => trim($text)]);
        }

        return ['fit' => $fit, 'note' => $note === '' ? null : $note];
    }

    /**
     * @param  array<string, array<string, mixed>>  $entries
     */
    private function write(array $entries, CapabilityImportResult $result): void
    {
        $note = __('admin.capabilities.import.version_note', ['date' => now()->format('Y.m.d.')]);
        $existing = Pattern::query()->whereIn('code', array_keys($entries))->get()->keyBy('code');
        $capabilities = Capability::query()->pluck('id', 'code');
        $preconditions = Precondition::query()->pluck('id', 'code');

        foreach ($entries as $code => $entry) {
            $pattern = $existing->get($code);
            $isNew = $pattern === null;
            $previousValidity = $pattern?->validity;
            $pattern ??= new Pattern(['code' => $code]);

            $pattern->fill([
                ...$entry['attributes'],
                'validity' => $entry['attributes']['validity'] ?? PatternValidity::Valid,
                'products' => $entry['attributes']['products'] ?? [],
                'where_visible' => $entry['attributes']['where_visible'] ?? [],
                'precondition_note' => filled($entry['attributes']['precondition_note'] ?? null) ? $entry['attributes']['precondition_note'] : null,
                'content' => array_filter($entry['content'], fn (?string $text): bool => filled($text)),
                'proposed_fields' => array_values(array_unique($entry['proposed'])),
                'field_states' => $entry['states'],
            ]);
            $pattern->save();

            $links = [];

            foreach ($entry['capabilities'] as $position => $capabilityCode) {
                if (! $capabilities->has($capabilityCode)) {
                    $result->warnings[] = __('admin.capabilities.import.pattern_unknown_capability', ['code' => $code, 'capability' => $capabilityCode]);

                    continue;
                }

                $fit = $entry['fits'][$capabilityCode] ?? ['fit' => null, 'note' => null];
                $links[$capabilities->get($capabilityCode)] = ['fit' => $fit['fit']?->value, 'fit_note' => $fit['note'], 'position' => $position];
            }

            $pattern->capabilities()->sync($links);

            $pattern->preconditions()->sync(collect(array_keys($entry['preconditions']))
                ->filter(fn (string $precondition): bool => $preconditions->has($precondition))
                ->values()
                ->mapWithKeys(fn (string $precondition, int $position): array => [
                    $preconditions->get($precondition) => ['note' => $entry['preconditions'][$precondition], 'position' => $position],
                ])
                ->all());

            $version = $pattern->recordVersion($note);

            // The counters of the Sheet make no version: they follow its Találat sheet.
            $pattern->forceFill([
                ...collect(self::COUNTERS)->mapWithKeys(fn (string $counter): array => [$counter => $entry['counters'][$counter] ?? 0])->all(),
                'sheet_maturity' => $entry['sheet_maturity'] ?? null,
                'imported_at' => $version !== null || $pattern->imported_at === null ? now() : $pattern->imported_at,
            ])->save();

            if (($entry['sheet_maturity'] ?? null) !== null && $entry['sheet_maturity'] !== $pattern->maturity()) {
                $result->warnings[] = __('admin.capabilities.import.pattern_maturity_differs', [
                    'code' => $code,
                    'sheet' => $entry['sheet_maturity']->label(),
                    'computed' => $pattern->maturity()->label(),
                ]);
            }

            match (true) {
                $isNew => $result->patternsCreated[] = ['code' => $code, 'name' => $pattern->name],
                $version !== null => $result->patternsUpdated[] = ['code' => $code, 'name' => $pattern->name, 'fields' => $version->changed_fields],
                default => $result->patternsUnchanged[] = $code,
            };

            if (! $isNew && $previousValidity === PatternValidity::Valid && $pattern->validity !== PatternValidity::Valid) {
                $result->patternsInvalidated[] = $code;
            }
        }
    }
}
