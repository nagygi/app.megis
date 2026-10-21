<?php

namespace App\Services\Import;

use DateTimeInterface;
use InvalidArgumentException;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Reads the first sheet of an xlsx or csv file as rows keyed by the header
 * row, for the one-off data loads (03 · 3.6), or named sheets (07 · 7.6).
 */
class SpreadsheetReader
{
    /**
     * @return list<string>
     */
    public static function supportedExtensions(): array
    {
        return ['xlsx', 'csv'];
    }

    /**
     * @return array{headers: list<string>, rows: array<int, array<string, string>>} rows keyed by their line number
     */
    public function read(string $path): array
    {
        $reader = $this->readerFor($path);
        $reader->open($path);

        $headers = [];
        $rows = [];

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $lineNumber => $row) {
                    $cells = $this->cellValues($row);

                    if ($headers === []) {
                        $headers = array_map('trim', $cells);

                        continue;
                    }

                    if (implode('', $cells) === '') {
                        continue;
                    }

                    $rows[$lineNumber] = array_combine(
                        $headers,
                        array_pad(array_slice($cells, 0, count($headers)), count($headers), ''),
                    );
                }

                break;
            }
        } finally {
            $reader->close();
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * Reads only the named sheets of an xlsx file; any other sheet is skipped
     * without reading a row of it (07 · 7.6, NM-19: the Bizonyíték, Kapcsolat,
     * Találat and Esettár sheets hold case data and never come in).
     *
     * @param  list<string>  $names
     * @return array<string, array{headers: list<string>, rows: array<int, array<string, string>>}> by sheet name, the missing ones left out
     */
    public function readSheets(string $path, array $names): array
    {
        $reader = new XlsxReader;
        $reader->open($path);

        $sheets = [];

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $name = trim($sheet->getName());

                if (! in_array($name, $names, true) || isset($sheets[$name])) {
                    continue;
                }

                $headers = [];
                $rows = [];

                foreach ($sheet->getRowIterator() as $lineNumber => $row) {
                    $cells = $this->cellValues($row);

                    if ($headers === []) {
                        $headers = array_map('trim', $cells);

                        continue;
                    }

                    if (implode('', $cells) === '') {
                        continue;
                    }

                    $rows[$lineNumber] = array_combine(
                        $headers,
                        array_pad(array_slice($cells, 0, count($headers)), count($headers), ''),
                    );
                }

                $sheets[$name] = ['headers' => $headers, 'rows' => $rows];
            }
        } finally {
            $reader->close();
        }

        return $sheets;
    }

    /**
     * The cells of a row as text; a formula cell gives the value the
     * spreadsheet computed, not the formula (the counters of the mégis tár).
     *
     * @return list<string>
     */
    public function cellValues(Row $row): array
    {
        $cells = $row->cells === [] ? [] : array_fill(0, max(array_keys($row->cells)) + 1, '');

        foreach ($row->cells as $index => $cell) {
            $cells[$index] = $this->cellText($cell instanceof FormulaCell ? $cell->getComputedValue() : $cell->getValue());
        }

        return $cells;
    }

    private function readerFor(string $path): ReaderInterface
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'xlsx' => new XlsxReader,
            'csv' => new CsvReader,
            default => throw new InvalidArgumentException("Unsupported file type: {$path}"),
        };
    }

    private function cellText(mixed $cell): string
    {
        return match (true) {
            $cell instanceof DateTimeInterface => $cell->format('Y-m-d'),
            is_bool($cell) => $cell ? '1' : '0',
            default => trim((string) $cell),
        };
    }
}
