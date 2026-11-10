<?php

namespace App\Services\ExternalClients;

use App\Enums\ExternalClientItemKind;
use App\Models\ExternalClient;
use App\Models\Organization;
use App\Models\Process;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The organization's clients on its Ügyfelek tab (35): their data, their users
 * and what we measure at them, each with its own question.
 */
class ExternalClientService
{
    public function __construct(private ExternalClientMeasurementService $measurements) {}

    /**
     * @param  array{name: string, description: ?string, active: bool, value_question?: ?string, measures_any_value?: bool}  $attributes
     * @param  list<array{email: string, first_name?: ?string, last_name?: ?string}>  $users
     * @param  list<array{kind: ExternalClientItemKind, value_key?: ?string, process_id?: ?int, question?: ?string}>  $items
     */
    public function save(Organization $organization, ?ExternalClient $client, array $attributes, array $users, array $items): ExternalClient
    {
        $attached = [];

        $client = DB::transaction(function () use ($organization, $client, $attributes, $users, $items, &$attached): ExternalClient {
            $data = [
                'name' => trim($attributes['name']),
                'description' => filled($attributes['description']) ? $attributes['description'] : null,
                'value_question' => filled($attributes['value_question'] ?? null) ? trim($attributes['value_question']) : null,
                'measures_any_value' => (bool) ($attributes['measures_any_value'] ?? false),
                'deactivated_at' => $attributes['active'] ? null : ($client?->deactivated_at ?? now()),
            ];

            if ($client === null) {
                $client = $organization->externalClients()->create($data);
            } else {
                $client->update($data);
            }

            $attached = $client->users()->sync($this->userIds($users))['attached'];
            $this->syncItems($client, $items);

            return $client;
        });

        // A user added while the client's diagnoses run is invited at once (35 · 35.5).
        $this->measurements->usersAdded($client, array_map('intval', $attached), auth()->user());

        return $client;
    }

    /**
     * The values of the organization's current value set, in its order: key => name.
     *
     * @return array<string, string>
     */
    public function measurableValues(Organization $organization): array
    {
        return $organization->valueSet?->items()->get()->pluck('name', 'key')->all() ?? [];
    }

    /**
     * The processes a client can be asked about: the accepted top-level ones
     * that are measured, by name (11 · 11.2).
     *
     * @return Collection<int, Process>
     */
    public function measurableProcesses(Organization $organization): Collection
    {
        return Process::query()->whereBelongsTo($organization)->measured()->orderBy('name')->get();
    }

    /**
     * An admin account cannot answer as a client: it logs in on the admin side.
     */
    public function isAdminEmail(string $email): bool
    {
        return User::query()->where('email', Str::lower(trim($email)))->whereNotNull('role')->exists();
    }

    /**
     * Existing accounts are found by e-mail; a new one gets the names given.
     *
     * @param  list<array{email: string, first_name?: ?string, last_name?: ?string}>  $users
     * @return list<int>
     */
    private function userIds(array $users): array
    {
        return collect($users)
            ->map(function (array $row): int {
                $email = Str::lower(trim($row['email']));

                return (User::query()->firstWhere('email', $email) ?? User::create([
                    'email' => $email,
                    'first_name' => filled($row['first_name'] ?? null) ? trim($row['first_name']) : null,
                    'last_name' => filled($row['last_name'] ?? null) ? trim($row['last_name']) : null,
                ]))->id;
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * The items in the given order. One that stays keeps its row, so the
     * answers already given to it keep pointing at it.
     *
     * @param  list<array{kind: ExternalClientItemKind, value_key?: ?string, process_id?: ?int, question?: ?string}>  $items
     */
    private function syncItems(ExternalClient $client, array $items): void
    {
        $existing = $client->items()->get()->keyBy(fn ($item) => $this->itemKey($item->kind, $item->value_key, $item->process_id));
        $keep = [];

        foreach (array_values($items) as $position => $item) {
            $key = $this->itemKey($item['kind'], $item['value_key'] ?? null, $item['process_id'] ?? null);
            $attributes = [
                'kind' => $item['kind'],
                'value_key' => $item['kind'] === ExternalClientItemKind::Value ? $item['value_key'] : null,
                'process_id' => $item['kind'] === ExternalClientItemKind::Process ? $item['process_id'] : null,
                // A value is asked with the client's value question (35 · 35.3).
                'question' => $item['kind'] === ExternalClientItemKind::Process ? trim((string) $item['question']) : null,
                'position' => $position,
            ];

            $row = $existing->get($key);

            if ($row === null) {
                $row = $client->items()->create($attributes);
            } else {
                $row->update($attributes);
            }

            $keep[] = $row->id;
        }

        $client->items()->whereNotIn('id', $keep)->delete();
    }

    private function itemKey(ExternalClientItemKind $kind, ?string $valueKey, ?int $processId): string
    {
        return $kind === ExternalClientItemKind::Value ? 'value:'.$valueKey : 'process:'.$processId;
    }
}
