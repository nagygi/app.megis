<?php

namespace App\Livewire\Admin\Materials;

use App\Enums\MaterialContext;
use App\Enums\MaterialStatus;
use App\Enums\MembershipStatus;
use App\Models\Material;
use App\Models\Membership;
use App\Services\Materials\MaterialPipeline;
use App\Services\Materials\MaterialStore;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * A material on the admin side (06 · 6.3, 6.5, 6.6): its processing state,
 * the speakers to assign after an upload, the transcript and the audio.
 */
class Show extends Component
{
    public Material $material;

    /**
     * Speaker index => user id ("" skips the speaker).
     *
     * @var array<int, string>
     */
    public array $assignments = [];

    public function mount(Material $material): void
    {
        Gate::authorize('view', $material);

        $this->material = $material;

        foreach ($material->speakers() as $index => $speaker) {
            $this->assignments[$index] = (string) ($material->speaker_assignments[$speaker] ?? '');
        }
    }

    /**
     * Each speaker becomes one user of the organization, or is left out (06 · 6.3).
     */
    public function assign(MaterialPipeline $pipeline): void
    {
        Gate::authorize('update', $this->material);
        abort_unless($this->material->status === MaterialStatus::AwaitingSpeakers, 403);

        $memberIds = $this->memberIds();

        $this->validate([
            'assignments' => ['array'],
            'assignments.*' => ['nullable', Rule::in(array_map('strval', $memberIds))],
        ]);

        $speakers = $this->material->speakers();
        $mapped = [];

        foreach ($speakers as $index => $speaker) {
            $userId = $this->assignments[$index] ?? '';
            $mapped[$speaker] = $userId === '' ? null : (int) $userId;
        }

        if (array_filter($mapped) === []) {
            $this->addError('assignments', __('admin.materials.assign_one'));

            return;
        }

        $pipeline->assignSpeakers($this->material, $mapped);
        session()->flash('status', __('admin.materials.analysis_started'));
    }

    public function retry(MaterialPipeline $pipeline): void
    {
        Gate::authorize('update', $this->material);
        abort_unless($this->material->status === MaterialStatus::Failed, 403);

        if ($this->material->context === MaterialContext::AdminUpload && filled($this->material->text) && $this->material->speaker_assignments) {
            $pipeline->assignSpeakers($this->material, $this->material->speaker_assignments);

            return;
        }

        $pipeline->start($this->material);
    }

    /**
     * The admin may delete the recording too; the transcript stays (06 · 6.6).
     */
    public function deleteAudio(MaterialStore $materials): void
    {
        Gate::authorize('update', $this->material);

        $materials->deleteAudio($this->material);
        session()->flash('status', __('admin.materials.audio_deleted'));
    }

    public function render(): View
    {
        $this->material->refresh();

        return view('livewire.admin.materials.show', [
            'speakers' => collect($this->material->speakers())->map(fn (string $speaker) => [
                'label' => $speaker,
                'samples' => collect($this->material->segments ?? [])
                    ->where('speaker', $speaker)
                    ->take(3)
                    ->map(fn (array $segment) => Str::limit($segment['text'], 160))
                    ->values()
                    ->all(),
            ]),
            'members' => $this->material->organization === null ? collect() : Membership::query()
                ->where('organization_id', $this->material->organization_id)
                ->where('status', '!=', MembershipStatus::Inactive)
                ->with('user')
                ->get()
                ->sortBy(fn (Membership $membership) => $membership->user->name),
            'diagnoses' => $this->material->diagnoses()->with('user')->get(),
        ])->layout('layouts::admin')->title($this->material->original_name ?? $this->material->context->label());
    }

    /**
     * @return list<int>
     */
    private function memberIds(): array
    {
        return Membership::query()
            ->where('organization_id', $this->material->organization_id)
            ->where('status', '!=', MembershipStatus::Inactive)
            ->pluck('user_id')
            ->all();
    }
}
