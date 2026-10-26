<?php

namespace App\Services\Processes;

use App\Ai\AiContext;
use App\Ai\Tasks\ParseProcessesTask;
use App\Enums\ProcessRhythm;
use App\Models\Organization;
use App\Models\Process;
use App\Services\Ai\AiGateway;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Several processes at once from a written or spoken text (11 · 11.2, 11.6):
 * the AI reads the rows, the user edits them, then they are added by hand.
 *
 * A row's parent is "new:{key}" (another row) or "process:{id}" (an existing
 * top-level process). Two levels at most, as everywhere (11 · 11.2).
 */
class ProcessImporter
{
    /**
     * The longest text the processes are read from.
     */
    public const TEXT_MAX = 10000;

    public function __construct(
        private AiGateway $ai,
        private ProcessService $processes,
    ) {}

    /**
     * The rows of the preview. An existing process is not offered again; its
     * subprocesses from the text go under it.
     *
     * @return list<array{key: string, name: string, rhythm: ?string, description: ?string, parent: ?string}>
     */
    public function parse(Organization $organization, string $text): array
    {
        $existing = Process::query()->whereBelongsTo($organization)->whereNull('parent_id')->orderBy('id')->get(['id', 'name'])
            ->mapWithKeys(fn (Process $process, int $index) => ['E'.($index + 1) => $process]);

        $result = $this->ai->run(
            new ParseProcessesTask(trim($text), $existing->map(fn (Process $process, string $ref) => ['ref' => $ref, 'name' => $process->name])->values()->all()),
            AiContext::forOrganization($organization),
        );

        $known = $this->namesOf($organization);
        $targets = $existing->map(fn (Process $process) => 'process:'.$process->id)->all();
        $rows = [];
        $keysByName = [];

        foreach ($result->structured['processes'] ?? [] as $item) {
            $name = mb_substr(trim((string) ($item['name'] ?? '')), 0, 255);
            $ref = (string) ($item['ref'] ?? '');
            $lower = mb_strtolower($name);

            if (mb_strlen($name) < 2) {
                continue;
            }

            if ($known->has($lower) || isset($keysByName[$lower])) {
                // Already there or already a row: its subprocesses from the text go under it.
                $target = isset($keysByName[$lower])
                    ? 'new:'.$keysByName[$lower]
                    : ($known->get($lower)->parent_id === null ? 'process:'.$known->get($lower)->id : null);

                if ($ref !== '' && $target !== null) {
                    $targets[$ref] ??= $target;
                }

                continue;
            }

            $key = 'r'.(count($rows) + 1);
            $keysByName[$lower] = $key;

            if ($ref !== '') {
                $targets[$ref] ??= 'new:'.$key;
            }

            $rows[$key] = [
                'key' => $key,
                'name' => $name,
                'rhythm' => ProcessRhythm::tryFrom((string) ($item['rhythm'] ?? ''))?->value,
                'description' => filled($item['description'] ?? null) ? mb_substr(trim((string) $item['description']), 0, 2000) : null,
                'parent' => (string) ($item['parent_ref'] ?? ''),
            ];
        }

        $parents = array_map(fn (array $row) => $targets[$row['parent']] ?? null, $rows);

        foreach ($rows as $key => $row) {
            $rows[$key]['parent'] = $this->topOf($parents[$key], $parents, $key);
        }

        return array_values($rows);
    }

    /**
     * Top-level rows first, then their subprocesses; a name the organization
     * already has is not added again.
     *
     * @param  list<array{key: string, name: string, rhythm: ?string, description: ?string, parent: ?string}>  $rows
     */
    public function import(Organization $organization, array $rows): int
    {
        return DB::transaction(function () use ($organization, $rows): int {
            $known = $this->namesOf($organization);
            $created = [];
            $count = 0;

            $ordered = collect($rows)->sortBy(fn (array $row) => str_starts_with((string) $row['parent'], 'new:') ? 1 : 0);

            foreach ($ordered as $row) {
                $name = trim($row['name']);
                $existing = $known->get(mb_strtolower($name));

                if ($existing !== null) {
                    if ($existing->parent_id === null) {
                        $created[$row['key']] = $existing;
                    }

                    continue;
                }

                $process = $this->processes->addManual(
                    $organization,
                    $name,
                    ProcessRhythm::tryFrom((string) $row['rhythm']),
                    $this->parentOf($organization, (string) $row['parent'], $created),
                    $row['description'],
                );

                $created[$row['key']] = $process;
                $known->put(mb_strtolower($name), $process);
                $count++;
            }

            return $count;
        });
    }

    /**
     * Two levels at most: a process the AI nests deeper goes under the top one
     * of its line; a circle leaves the row on the top level.
     *
     * @param  array<string, ?string>  $parents  row key => parent
     */
    private function topOf(?string $parent, array $parents, string $key): ?string
    {
        $seen = [$key];

        while ($parent !== null && str_starts_with($parent, 'new:')) {
            $next = substr($parent, strlen('new:'));

            if (in_array($next, $seen, true)) {
                return null;
            }

            if (($parents[$next] ?? null) === null) {
                return $parent;
            }

            $seen[] = $next;
            $parent = $parents[$next];
        }

        return $parent;
    }

    /**
     * @param  array<string, Process>  $created
     */
    private function parentOf(Organization $organization, string $parent, array $created): ?Process
    {
        [$type, $id] = array_pad(explode(':', $parent, 2), 2, '');

        return match ($type) {
            'new' => $created[$id] ?? null,
            'process' => Process::query()->whereBelongsTo($organization)->whereNull('parent_id')->find((int) $id),
            default => null,
        };
    }

    /**
     * The organization's processes by lower-case name, compared in PHP so
     * accented capitals match on every database.
     *
     * @return Collection<string, Process>
     */
    private function namesOf(Organization $organization): Collection
    {
        return Process::query()->whereBelongsTo($organization)->get(['id', 'name', 'parent_id'])
            ->keyBy(fn (Process $process) => mb_strtolower($process->name));
    }
}
