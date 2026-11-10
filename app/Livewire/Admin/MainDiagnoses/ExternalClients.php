<?php

namespace App\Livewire\Admin\MainDiagnoses;

use App\Enums\MainDiagnosisStage;
use App\Models\Diagnosis;
use App\Services\ExternalClients\ExternalClientMeasurementService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * "Ügyfélmérés" of a main diagnosis (35 · 35.8): per client its users and
 * where they stand, their answers with the question asked and the material,
 * and the client's subdiagnosis. Only when the diagnosis has clients.
 */
class ExternalClients extends Component
{
    public Diagnosis $diagnosis;

    public function mount(Diagnosis $diagnosis): void
    {
        Gate::authorize('view', $diagnosis);
        abort_if($diagnosis->main === null || ! $diagnosis->main->externalClients()->exists(), 404);

        $this->diagnosis = $diagnosis;
    }

    /**
     * A new personal link, while the diagnosis runs (35 · 35.5).
     */
    public function resend(int $clientId, int $userId, ExternalClientMeasurementService $measurements): void
    {
        Gate::authorize('update', $this->diagnosis);

        $main = $this->diagnosis->main;
        abort_unless($main->stage === MainDiagnosisStage::Running, 403);

        $client = $main->externalClients()->findOrFail($clientId);
        $user = $client->users()->findOrFail($userId);

        $measurements->inviteUser($main, $client, $user, auth('admin')->user());
        session()->flash('status', __('admin.external_clients.invitation_sent', ['email' => $user->email]));
    }

    public function render(ExternalClientMeasurementService $measurements): View
    {
        $main = $this->diagnosis->main;

        return view('livewire.admin.main-diagnoses.external-clients', [
            'main' => $main,
            'clients' => $main->externalClients()->with(['users' => fn ($users) => $users->orderBy('last_name')->orderBy('first_name')])->orderBy('name')->get(),
            'answers' => $main->externalClientAnswers()
                ->whereNotNull('submitted_at')
                ->with(['material', 'process', 'user'])
                ->orderBy('user_id')
                ->orderBy('id')
                ->get()
                ->groupBy('external_client_id'),
            'subdiagnoses' => Diagnosis::query()
                ->where('parent_id', $this->diagnosis->id)
                ->whereNotNull('external_client_id')
                ->get()
                ->keyBy('external_client_id'),
            'states' => $measurements->userStates($main),
            'valueNames' => $measurements->valueNames($main),
            'canResend' => $main->stage === MainDiagnosisStage::Running,
        ])->layout('layouts::admin')->title($this->diagnosis->name);
    }
}
