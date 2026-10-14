<?php

namespace App\Livewire\Admin\Diagnoses;

use App\Enums\DiagnosisCreator;
use App\Enums\DiagnosisLevel;
use App\Enums\DiagnosisStatus;
use App\Enums\MainDiagnosisStage;
use App\Enums\MainDiagnosisSubject;
use App\Enums\ParticipantRole;
use App\Livewire\Admin\Concerns\WithDataTable;
use App\Models\Diagnosis;
use App\Models\DiagnosisType;
use App\Models\InboxItem;
use App\Models\MainDiagnosis;
use App\Models\Organization;
use App\Models\User;
use App\Services\Diagnoses\ThresholdPolicy;
use App\Services\Inbox\InboxService;
use App\Services\MainDiagnoses\StepProgress;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Diagnózisok: the garden of the main diagnoses by stage (A0, 19 · 19.2), and
 * with a switch the list of every diagnosis (04 · 4.2).
 */
class Index extends Component
{
    use WithDataTable;

    /**
     * The stages of the garden's columns: soil, root, trunk, growth rings, fruit (19 · 19.2).
     */
    public const GARDEN_STAGES = ['invited' => 'soil', 'filling' => 'root', 'running' => 'trunk', 'reading' => 'growth-rings', 'published' => 'fruit'];

    #[Url(as: 'nezet', except: 'kert')]
    public string $view = 'kert';

    #[Url(as: 'alany', except: '')]
    public string $subject = '';

    #[Url(as: 'kapcsolattarto', except: '')]
    public string $contact = '';

    /** Draft and closed diagnoses too. */
    #[Url(as: 'mind', except: false)]
    public bool $showAll = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', Diagnosis::class);
    }

    public function render(): View
    {
        if ($this->view !== 'lista') {
            return $this->garden();
        }

        $query = Diagnosis::query()->with(['organization', 'team', 'user', 'type', 'creator', 'main']);

        if ($term = $this->searchTerm()) {
            $query->whereLike('name', $term);
        }

        if ($organization = $this->filter('organization')) {
            $query->where('organization_id', $organization);
        }

        if ($type = $this->filter('type')) {
            $query->where('diagnosis_type_id', $type);
        }

        foreach (['level' => DiagnosisLevel::class, 'creator_kind' => DiagnosisCreator::class, 'status' => DiagnosisStatus::class] as $column => $enum) {
            if (($case = $enum::tryFrom((string) $this->filter($column))) !== null) {
                $query->where($column, $case);
            }
        }

        // The stage of a main diagnosis (17 · 17.5): the MVP overview (19).
        if (($stage = MainDiagnosisStage::tryFrom((string) $this->filter('stage'))) !== null) {
            $query->whereHas('main', fn ($main) => $main->where('stage', $stage));
        }

        $enumOptions = fn (string $enum) => collect($enum::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all();

        return view('livewire.admin.diagnoses.index', [
            'diagnoses' => $this->applySorting($query)->paginate(25),
            'organizations' => Organization::query()->orderBy('name')->pluck('name', 'id')->all(),
            'types' => DiagnosisType::query()->orderBy('name')->pluck('name', 'id')->all(),
            'levels' => $enumOptions(DiagnosisLevel::class),
            'creators' => $enumOptions(DiagnosisCreator::class),
            'statuses' => $enumOptions(DiagnosisStatus::class),
            'stages' => $enumOptions(MainDiagnosisStage::class),
        ])->layout('layouts::admin')->title(__('admin.diagnoses.title'));
    }

    private function garden(): View
    {
        $stages = $this->showAll
            ? ['draft' => 'spiral', ...self::GARDEN_STAGES, 'closed' => 'shade']
            : self::GARDEN_STAGES;

        $mains = MainDiagnosis::query()
            ->whereIn('stage', array_keys($stages))
            ->when(MainDiagnosisSubject::tryFrom($this->subject), fn ($query, MainDiagnosisSubject $subject) => $query->where('subject', $subject))
            ->when($this->contact !== '', fn ($query) => $query->whereHas('diagnosis.organization', fn ($organization) => $organization->where('contact_admin_id', $this->contact === 'en' ? auth('admin')->id() : (int) $this->contact)))
            ->with(['diagnosis.organization', 'diagnosis.team', 'leader', 'participants'])
            ->latest('id')
            ->get();
        $attention = InboxItem::query()->open()->whereIn('diagnosis_id', $mains->pluck('diagnosis_id'))->pluck('diagnosis_id')->unique()->flip();
        $progress = app(StepProgress::class);
        $threshold = app(ThresholdPolicy::class);

        $cards = $mains->map(function (MainDiagnosis $main) use ($attention, $progress, $threshold): array {
            $count = match (true) {
                in_array($main->stage, [MainDiagnosisStage::Draft, MainDiagnosisStage::Invited, MainDiagnosisStage::Filling], true) => (function () use ($main, $progress) {
                    $steps = $progress->forLeader($main, $main->leader);

                    return __('admin.garden.counts.steps', ['done' => $steps->doneCount(), 'total' => $steps->count()]);
                })(),
                $main->stage === MainDiagnosisStage::Running && $main->hasPeers() => __('admin.garden.counts.peers', [
                    'answered' => $threshold->peersAnswered($main),
                    'total' => $main->participants->where('role', ParticipantRole::Peer)->whereNull('excluded_at')->count(),
                ]),
                $main->stage === MainDiagnosisStage::Running => (function () use ($main, $progress) {
                    $steps = $progress->forLeader($main, $main->leader);

                    return __('admin.garden.counts.steps', ['done' => $steps->doneCount(), 'total' => $steps->count()]);
                })(),
                $main->stage === MainDiagnosisStage::Reading => __('admin.garden.counts.measuring_points', ['count' => $main->calculation['measuring_points'] ?? 0]),
                $main->stage === MainDiagnosisStage::Published => __('admin.garden.counts.published', ['date' => $main->published_at?->format('m.d.') ?? '–']),
                default => null,
            };

            return [
                'main' => $main,
                'count' => $count,
                'attention' => $attention->has($main->diagnosis_id),
                // A card that just reached the fruit column flashes once (19 · 19.2).
                'fresh' => $main->stage === MainDiagnosisStage::Published && $main->published_at?->isAfter(now()->subDay()),
            ];
        });

        return view('livewire.admin.diagnoses.garden', [
            'columns' => collect($stages)->map(fn (string $symbol, string $stage) => [
                'stage' => MainDiagnosisStage::from($stage),
                'symbol' => $symbol,
                'cards' => $cards->filter(fn (array $card) => $card['main']->stage->value === $stage)->values(),
            ]),
            'openCount' => app(InboxService::class)->openCount(),
            'subjects' => collect(MainDiagnosisSubject::cases())->mapWithKeys(fn (MainDiagnosisSubject $subject) => [$subject->value => $subject->label()])->all(),
            'contacts' => User::query()->admins()->orderBy('last_name')->orderBy('first_name')->get(),
        ])->layout('layouts::admin')->title(__('admin.garden.title'));
    }

    protected function sortableColumns(): array
    {
        return [
            'name' => 'name',
            'organization' => Organization::query()->select('name')->whereColumn('organizations.id', 'diagnoses.organization_id'),
            'level' => 'level',
            'type' => DiagnosisType::query()->select('name')->whereColumn('diagnosis_types.id', 'diagnoses.diagnosis_type_id'),
            'date' => 'diagnosed_on',
            'creator' => 'creator_kind',
            'status' => 'status',
            'stage' => MainDiagnosis::query()->select('stage')->whereColumn('main_diagnoses.diagnosis_id', 'diagnoses.id'),
        ];
    }

    protected function defaultSort(): array
    {
        return ['date', 'desc'];
    }
}
