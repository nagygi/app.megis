<?php

namespace App\Services\Versioning;

/**
 * Field by field comparison of two versions (03 · 3.3): word level for
 * texts, item level for ordered lists.
 */
class SnapshotComparison
{
    public const TEXT = 'text';

    public const LIST = 'list';

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @param  array<string, string>  $fields  field => TEXT or LIST, in display order
     * @return list<array{field: string, type: string, changed: bool, old: list<array<string, string>>, new: list<array<string, string>>}>
     */
    public static function compare(array $old, array $new, array $fields): array
    {
        $rows = [];

        foreach ($fields as $field => $type) {
            if ($type === self::LIST) {
                $oldItems = array_values((array) ($old[$field] ?? []));
                $newItems = array_values((array) ($new[$field] ?? []));
                $diff = ListDiff::compare($oldItems, $newItems);

                $rows[] = [
                    'field' => $field,
                    'type' => $type,
                    'changed' => $oldItems !== $newItems,
                    'old' => $diff['old'],
                    'new' => $diff['new'],
                ];

                continue;
            }

            $oldText = (string) ($old[$field] ?? '');
            $newText = (string) ($new[$field] ?? '');
            $segments = TextDiff::compare($oldText, $newText);

            $rows[] = [
                'field' => $field,
                'type' => $type,
                'changed' => $oldText !== $newText,
                'old' => array_values(array_filter($segments, fn (array $segment): bool => $segment['type'] !== TextDiff::INSERT)),
                'new' => array_values(array_filter($segments, fn (array $segment): bool => $segment['type'] !== TextDiff::DELETE)),
            ];
        }

        return $rows;
    }
}
