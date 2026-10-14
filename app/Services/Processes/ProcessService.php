<?php

namespace App\Services\Processes;

use App\Enums\MetricRhythm;
use App\Enums\ParticipantRole;
use App\Enums\ProcessConfidence;
use App\Enums\ProcessKind;
use App\Enums\ProcessRhythm;
use App\Enums\ProcessSource;
use App\Exceptions\DiagnosisException;
use App\Models\MainDiagnosis;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Process;
use App\Models\ProcessMetric;
use App\Models\ProcessParticipant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Processes of an organization (11): the same rules for the leader on V6·d–V6·f,
 * in Tárak → Folyamatok and for the admin on the organization's tab (11 · 11.6).
 */
class ProcessService
{
    /**
     * Tárak → Folyamatok is for the organization's leaders and the leaders of
     * its diagnoses (11 · 11.5).
     */
    public static function canManageLibrary(User $user, Organization $organization): bool
    {
        return Membership::query()->whereBelongsTo($organization)->where('user_id', $user->id)->where('is_organization_leader', true)->exists()
            || MainDiagnosis::query()
                ->withParticipant($user, ParticipantRole::Leader)
                ->whereHas('diagnosis', fn ($query) => $query->where('organization_id', $organization->id))
                ->exists();
    }

    /**
     * "Folyamat vagy alfolyamat hozzáadása": "nincs a naptárban · 0 alkalom" (11 · 11.2).
     */
    public function addManual(Organization $organization, string $name, ?ProcessRhythm $rhythm, ?Process $parent = null): Process
    {
        if ($parent !== null) {
            $this->ensureCanHaveChildren($parent, $organization);
        }

        return Process::create([
            'organization_id' => $organization->id,
            'parent_id' => $parent?->id,
            'name' => trim($name),
            'rhythm' => $rhythm,
            'kind' => ProcessKind::Routine,
            'source' => ProcessSource::Manual,
            'confidence' => ProcessConfidence::Manual,
        ]);
    }

    /**
     * The rename leaves a trace: the old name, who and when (11 · 11.2).
     */
    public function rename(Process $process, string $name, ?User $actor): void
    {
        $name = trim($name);

        if ($name === $process->name) {
            return;
        }

        DB::transaction(function () use ($process, $name, $actor): void {
            $process->renames()->create(['old_name' => $process->name, 'new_name' => $name, 'created_by' => $actor?->id]);
            $process->update(['name' => $name]);
        });
    }

    /**
     * rutin · egyszeri · nem mérjük (11 · 11.2). Only a routine stays accepted.
     */
    public function setKind(Process $process, ProcessKind $kind): void
    {
        $process->update([
            'kind' => $kind,
            'accepted_at' => $kind === ProcessKind::Routine ? $process->accepted_at : null,
        ]);
    }

    /**
     * "Elfogadom" / "Visszavonom". In Snapshot · Belső at most two processes are measured (11, 18 · 18.1).
     */
    public function setAccepted(Process $process, bool $accepted, ?MainDiagnosis $main = null): void
    {
        if ($accepted && $process->parent_id === null && $process->kind === ProcessKind::Routine && ! $process->isAccepted()) {
            $this->ensureRoomFor($process->organization, $main);
        }

        $process->update(['accepted_at' => $accepted ? ($process->accepted_at ?? now()) : null]);
    }

    /**
     * "N folyamat elfogadása": every routine not accepted yet, within the limit.
     */
    public function acceptAll(Organization $organization, ?MainDiagnosis $main = null): int
    {
        $count = 0;

        foreach (Process::query()->whereBelongsTo($organization)->where('kind', ProcessKind::Routine)->whereNull('accepted_at')->orderBy('id')->get() as $process) {
            try {
                $this->setAccepted($process->setRelation('organization', $organization), true, $main);
                $count++;
            } catch (DiagnosisException) {
                break;
            }
        }

        return $count;
    }

    /**
     * How many more processes the diagnosis may measure; null without a limit.
     */
    public function room(Organization $organization, ?MainDiagnosis $main): ?int
    {
        $limit = $main?->productSettings()['max_measured_processes'] ?? null;

        return $limit === null ? null : max(0, $limit - Process::query()->whereBelongsTo($organization)->measured()->count());
    }

    public function setResponsible(Process $process, ?User $user): void
    {
        if ($user !== null) {
            $this->ensureMember($process->organization, $user);
        }

        $process->update(['responsible_user_id' => $user?->id]);
    }

    public function addParticipant(Process $process, User $user): void
    {
        $this->ensureMember($process->organization, $user);

        if (! $process->participants()->where('user_id', $user->id)->exists()) {
            $process->participants()->create(['user_id' => $user->id, 'email' => $user->email, 'source' => 'manual']);
        }
    }

    public function removeParticipant(Process $process, ProcessParticipant $participant): void
    {
        abort_unless($participant->process_id === $process->id, 404);

        $participant->delete();
    }

    /**
     * @param  list<int>  $teamIds
     */
    public function syncTeams(Process $process, array $teamIds): void
    {
        $process->teams()->sync($process->organization->teams()->whereIn('id', $teamIds)->pluck('id')->all());
    }

    /**
     * @param  array{name: string, unit?: ?string, rhythm: string, target?: ?string, description?: ?string}  $data
     */
    public function addMetric(Process $process, array $data): ProcessMetric
    {
        return $process->metrics()->create([
            'name' => trim($data['name']),
            'unit' => filled($data['unit'] ?? null) ? trim($data['unit']) : null,
            'rhythm' => MetricRhythm::from($data['rhythm']),
            'target' => filled($data['target'] ?? null) ? trim($data['target']) : null,
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
        ]);
    }

    /**
     * The responsible, the leader or an admin records a value with a date (11 · 11.3).
     */
    public function recordMetricValue(ProcessMetric $metric, float $value, string $measuredOn, User $actor): void
    {
        $metric->values()->create(['value' => $value, 'measured_on' => $measuredOn, 'created_by' => $actor->id]);
    }

    /**
     * What the diagnosis measures, recorded when V6·f is left (11 · 11.4).
     */
    public function syncDiagnosisProcesses(MainDiagnosis $main): void
    {
        $organization = $main->diagnosis->organization;
        $ids = Process::query()->whereBelongsTo($organization)->measured()->pluck('id');

        DB::transaction(function () use ($main, $ids): void {
            DB::table('main_diagnosis_processes')->where('main_diagnosis_id', $main->id)->whereNotIn('process_id', $ids)->delete();

            foreach ($ids as $id) {
                DB::table('main_diagnosis_processes')->insertOrIgnore([
                    'main_diagnosis_id' => $main->id,
                    'process_id' => $id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    /**
     * The four groups of V6·d (11 · 11.2): strong patterns and the conversation's
     * processes, uncertain ones, the ones added by hand, and the rest.
     *
     * @return array{strong: Collection<int, Process>, uncertain: Collection<int, Process>, manual: Collection<int, Process>, other: Collection<int, Process>}
     */
    public function groups(Organization $organization): array
    {
        $processes = Process::query()
            ->whereBelongsTo($organization)
            ->whereNull('parent_id')
            ->with(['children', 'responsible', 'mergeCandidate'])
            ->withCount('occasions')
            ->orderBy('id')
            ->get();

        $groups = ['strong' => collect(), 'uncertain' => collect(), 'manual' => collect(), 'other' => collect()];

        foreach ($processes as $process) {
            $group = match (true) {
                $process->kind === ProcessKind::OneOff => 'other',
                $process->source === ProcessSource::Manual => 'manual',
                $process->unclear || in_array($process->confidence, [ProcessConfidence::Medium, ProcessConfidence::Low], true) => 'uncertain',
                default => 'strong',
            };
            $groups[$group]->push($process);
        }

        return $groups;
    }

    private function ensureRoomFor(Organization $organization, ?MainDiagnosis $main): void
    {
        $room = $this->room($organization, $main);

        if ($room !== null && $room === 0) {
            throw new DiagnosisException(__('processes.limit_reached', ['max' => $main->productSettings()['max_measured_processes']]));
        }
    }

    private function ensureCanHaveChildren(Process $parent, Organization $organization): void
    {
        // Two levels at most: a subprocess has no subprocess (11 · 11.2).
        if ($parent->organization_id !== $organization->id || $parent->parent_id !== null) {
            throw new DiagnosisException(__('processes.two_levels'));
        }
    }

    private function ensureMember(Organization $organization, User $user): void
    {
        abort_unless(Membership::query()->whereBelongsTo($organization)->where('user_id', $user->id)->exists(), 422);
    }
}
