<?php

namespace App\Services\Import;

use DateTimeInterface;
use InvalidArgumentException;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Reads the first sheet of an xlsx or csv file as rows keyed by the header
 * row, for the one-off data loads (03 · 3.6, later 07 · 7.6).
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
                    $cells = array_map(fn (mixed $cell): string => $this->cellText($cell), $row->toArray());

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
