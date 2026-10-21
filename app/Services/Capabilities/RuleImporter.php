<?php

namespace App\Services\Capabilities;

use App\Enums\CapabilityRuleArea;
use App\Enums\CapabilityRuleStatus;
use App\Models\CapabilityRule;

/**
 * The Szabálykönyv sheet of the mégis tár, loaded with the capabilities and
 * the patterns. Matches by szabály-azonosító; the rules are read only, so
 * they have no versions: the preview lists the ones that change.
 */
class RuleImporter
{
    public const SHEET = 'Szabálykönyv';

    /**
     * The headers carry their options, e.g. "terület (nyom / állapot / …)":
     * they are matched by how they start.
     */
    private const COLUMNS = [
        'szabaly azonosito' => 'code',
        'terulet' => 'area',
        'szabaly szovege' => 'text',
        'statusz' => 'status',
        'forras' => 'source',
        'verzio' => 'version',
        'datum' => 'decided_on',
    ];

    private const AREAS = [
        'nyom' => CapabilityRuleArea::Trace,
        'allapot' => CapabilityRuleArea::State,
        'konfidencia' => CapabilityRuleArea::Confidence,
        'erettseg' => CapabilityRuleArea::Maturity,
        'prioritas' => CapabilityRuleArea::Priority,
        'vedelem' => CapabilityRuleArea::Protection,
        'elofeltetel' => CapabilityRuleArea::Precondition,
        'fejlesztes' => CapabilityRuleArea::Development,
    ];

    /**
     * The area by the prefix of the code, when the cell does not say it.
     */
    private const PREFIXES = [
        'NY' => CapabilityRuleArea::Trace,
        'AL' => CapabilityRuleArea::State,
        'KO' => CapabilityRuleArea::Confidence,
        'ER' => CapabilityRuleArea::Maturity,
        'PR' => CapabilityRuleArea::Priority,
        'VE' => CapabilityRuleArea::Protection,
        'EF' => CapabilityRuleArea::Precondition,
        'FE' => CapabilityRuleArea::Development,
    ];

    /**
     * Reads the sheet and writes it; the caller holds the transaction.
     *
     * @param  array{headers: list<string>, rows: array<int, array<string, string>>}  $sheet
     */
    public function load(array $sheet, CapabilityImportResult $result): void
    {
        $columns = [];

        foreach ($sheet['headers'] as $header) {
            $key = SheetCells::normalize($header);

            foreach (self::COLUMNS as $prefix => $field) {
                if (str_starts_with($key, $prefix) && ! in_array($field, $columns, true)) {
                    $columns[$header] = $field;

                    break;
                }
            }
        }

        if (! in_array('code', $columns, true) || ! in_array('text', $columns, true)) {
            $result->errors[] = __('admin.capabilities.import.rules_missing_columns');

            return;
        }

        $existing = CapabilityRule::query()->get()->keyBy('code');
        $seen = [];

        foreach ($sheet['rows'] as $line => $row) {
            $values = [];

            foreach ($columns as $header => $field) {
                $values[$field] = trim($row[$header] ?? '');
            }

            $code = mb_strtoupper($values['code']);

            if ($code === '' || $values['text'] === '') {
                $result->errors[] = __('admin.capabilities.import.rule_row_error', ['line' => $line, 'code' => $code === '' ? '–' : $code]);

                continue;
            }

            if (isset($seen[$code])) {
                $result->errors[] = __('admin.capabilities.import.rule_duplicate_code', ['line' => $line, 'code' => $code]);

                continue;
            }

            $seen[$code] = true;
            $rule = $existing->get($code) ?? new CapabilityRule(['code' => $code]);

            $rule->fill([
                'area' => self::AREAS[SheetCells::normalize($values['area'] ?? '')] ?? self::PREFIXES[substr($code, 0, 2)] ?? null,
                'text' => $values['text'],
                'status' => self::status($values['status'] ?? ''),
                'status_label' => filled($values['status'] ?? null) ? mb_substr($values['status'], 0, 255) : null,
                'source' => filled($values['source'] ?? null) ? $values['source'] : null,
                'version' => filled($values['version'] ?? null) ? mb_substr($values['version'], 0, 255) : null,
                'decided_on' => filled($values['decided_on'] ?? null) ? mb_substr($values['decided_on'], 0, 255) : null,
            ]);

            if ($rule->exists && ! $rule->isDirty()) {
                continue;
            }

            $rule->imported_at = now();
            $rule->save();
            $result->rulesChanged[] = $code;
        }
    }

    /**
     * "megállapodott", "döntés 09.27.", "javaslat, elfogadva", "javaslat", or
     * a combination such as "megállapodott és javaslat": the first one counts.
     */
    private static function status(string $label): ?CapabilityRuleStatus
    {
        $key = SheetCells::normalize($label);

        return match (true) {
            $key === '' => null,
            str_starts_with($key, 'megallapodott') => CapabilityRuleStatus::Agreed,
            str_starts_with($key, 'dontes') => CapabilityRuleStatus::Decided,
            str_starts_with($key, 'javaslat elfogadva') => CapabilityRuleStatus::Accepted,
            str_starts_with($key, 'javaslat') => CapabilityRuleStatus::Proposal,
            default => null,
        };
    }
}
