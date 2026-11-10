<?php

namespace App\Livewire\Admin\Organizations;

use App\Enums\ExternalClientItemKind;
use App\Models\ExternalClient;
use App\Models\Organization;
use App\Models\User;
use App\Services\ExternalClients\ExternalClientService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Creating or editing a client of the organization, on a page of its own under
 * the Ügyfelek tab (35 · 35.2, 35.3): its data, its users, and which values and
 * processes we measure at it, each with the question its users are asked.
 */
class ExternalClientForm extends Component
{
    public Organization $organization;

    public ?ExternalClient $externalClient = null;

    public string $name = '';

    public string $description = '';

    public bool $active = true;

    /**
     * The one question for the values; they are measured from its answer (35 · 35.3).
     */
    public string $valueQuestion = '';

    /**
     * The client's users; a new one has no id until saved.
     *
     * @var list<array{id: ?int, email: string, first_name: string, last_name: string}>
     */
    public array $users = [];

    public string $newLastName = '';

    public string $newFirstName = '';

    public string $newEmail = '';

    /**
     * The organization's values: value set item key => selected.
     *
     * @var array<string, bool>
     */
    public array $values = [];

    /**
     * The organization's measured processes: process id => selected and its question.
     *
     * @var array<int, array{selected: bool, question: string}>
     */
    public array $processes = [];

    public function mount(Organization $organization, ?ExternalClient $externalClient = null): void
    {
        Gate::authorize('update', $organization);
        abort_unless($organization->usesExternalClients(), 404);

        $clients = app(ExternalClientService::class);
        $this->organization = $organization;
        $items = collect();

        if ($externalClient?->exists) {
            $this->externalClient = $externalClient;
            $this->name = $externalClient->name;
            $this->description = (string) $externalClient->description;
            $this->active = $externalClient->isActive();
            $this->valueQuestion = $externalClient->valueQuestion();
            $this->users = $externalClient->users()->orderBy('last_name')->orderBy('first_name')->get()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'first_name' => (string) $user->first_name,
                    'last_name' => (string) $user->last_name,
                ])
                ->all();
            $items = $externalClient->items()->get();
        }

        // A new client measures every value of the organization, with the default question.
        if ($this->externalClient === null) {
            $this->valueQuestion = __('external_clients.default_questions.values');
        }

        foreach (array_keys($clients->measurableValues($organization)) as $key) {
            $this->values[$key] = $this->externalClient === null
                || $items->contains(fn ($item) => $item->kind === ExternalClientItemKind::Value && $item->value_key === $key);
        }

        foreach ($clients->measurableProcesses($organization) as $process) {
            $item = $items->first(fn ($item) => $item->kind === ExternalClientItemKind::Process && $item->process_id === $process->id);

            $this->processes[$process->id] = [
                'selected' => $item !== null,
                'question' => $item?->question ?? __('external_clients.default_questions.process', ['process' => $process->name]),
            ];
        }
    }

    /**
     * Adds a user to the list; saving the client keeps them.
     */
    public function addUser(ExternalClientService $clients): void
    {
        $this->newEmail = Str::lower(trim($this->newEmail));

        $this->validate([
            'newEmail' => ['required', 'email', 'max:255'],
            'newLastName' => ['nullable', 'string', 'max:255'],
            'newFirstName' => ['nullable', 'string', 'max:255'],
        ]);

        if (collect($this->users)->contains('email', $this->newEmail)) {
            throw ValidationException::withMessages(['newEmail' => __('admin.external_clients.user_already_added')]);
        }

        if ($clients->isAdminEmail($this->newEmail)) {
            throw ValidationException::withMessages(['newEmail' => __('admin.external_clients.admin_email')]);
        }

        $existing = User::query()->firstWhere('email', $this->newEmail);

        $this->users[] = [
            'id' => $existing?->id,
            'email' => $this->newEmail,
            'first_name' => $existing !== null ? (string) $existing->first_name : trim($this->newFirstName),
            'last_name' => $existing !== null ? (string) $existing->last_name : trim($this->newLastName),
        ];

        $this->reset('newEmail', 'newFirstName', 'newLastName');
    }

    public function removeUser(int $index): void
    {
        unset($this->users[$index]);
        $this->users = array_values($this->users);
    }

    public function save(ExternalClientService $clients): void
    {
        Gate::authorize('update', $this->organization);
        abort_unless($this->organization->usesExternalClients(), 404);

        $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('external_clients', 'name')
                    ->where('organization_id', $this->organization->id)
                    ->ignore($this->externalClient?->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'valueQuestion' => ['nullable', 'string', 'max:2000'],
            'processes.*.question' => ['nullable', 'string', 'max:2000'],
        ], ['name.unique' => __('admin.external_clients.name_taken')]);

        $items = $this->selectedItems($clients);

        $client = $clients->save(
            $this->organization,
            $this->externalClient,
            ['name' => $this->name, 'description' => $this->description, 'active' => $this->active, 'value_question' => $this->valueQuestion],
            $this->users,
            $items,
        );

        session()->flash('status', __('admin.common.saved'));
        $this->redirectRoute('admin.organizations.external-clients.edit', [$this->organization, $client], navigate: false);
    }

    public function render(ExternalClientService $clients): View
    {
        $users = User::query()->whereIn('id', collect($this->users)->pluck('id')->filter())->get()->keyBy('id');

        return view('livewire.admin.organizations.external-client-form', [
            'isEditing' => $this->externalClient !== null,
            'valueNames' => $clients->measurableValues($this->organization),
            'processList' => $clients->measurableProcesses($this->organization)->keyBy('id'),
            'accounts' => $users,
        ])->layout('layouts::admin')->title($this->externalClient?->name ?? __('admin.external_clients.create'));
    }

    /**
     * The checked values and processes. The values need the value question,
     * a checked process its own.
     *
     * @return list<array{kind: ExternalClientItemKind, value_key?: string, process_id?: int, question?: string}>
     *
     * @throws ValidationException
     */
    private function selectedItems(ExternalClientService $clients): array
    {
        $items = [];
        $missing = [];
        $valueKeys = array_keys($clients->measurableValues($this->organization));
        $processIds = $clients->measurableProcesses($this->organization)->modelKeys();

        foreach ($this->values as $key => $selected) {
            if ($selected && in_array((string) $key, $valueKeys, true)) {
                $items[] = ['kind' => ExternalClientItemKind::Value, 'value_key' => (string) $key];
            }
        }

        if ($items !== [] && blank($this->valueQuestion)) {
            $missing['valueQuestion'] = __('admin.external_clients.value_question_required');
        }

        foreach ($this->processes as $processId => $process) {
            if (! ($process['selected'] ?? false) || ! in_array((int) $processId, $processIds, true)) {
                continue;
            }

            if (blank($process['question'] ?? null)) {
                $missing['processes.'.$processId.'.question'] = __('admin.external_clients.question_required');
            } else {
                $items[] = ['kind' => ExternalClientItemKind::Process, 'process_id' => (int) $processId, 'question' => $process['question']];
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages($missing);
        }

        return $items;
    }
}
