<?php

namespace App\Livewire\Admin\Inbox;

use App\Enums\InboxItemType;
use App\Jobs\DetectCapabilities;
use App\Jobs\DraftReading;
use App\Livewire\Admin\Concerns\WithDataTable;
use App\Mail\FeedbackReplyMail;
use App\Models\InboxItem;
use App\Models\MainDiagnosis;
use App\Models\MainDiagnosisParticipant;
use App\Models\Material;
use App\Models\MirrorSession;
use App\Models\Organization;
use App\Models\Pattern;
use App\Models\Process;
use App\Models\ReportFeedback;
use App\Models\User;
use App\Services\Inbox\InboxService;
use App\Services\MainDiagnoses\DiagnosisRunService;
use App\Services\MainDiagnoses\MainDiagnosisService;
use App\Services\Processes\OccasionService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Beérkező (22 · 22.1): open rows from every diagnosis; closed rows under
 * "Lezártak". The contact filter starts with the signed-in admin. A row,
 * a decision, at most two buttons (22 · 22.2).
 */
class Index extends Component
{
    use WithDataTable;

    /**
     * Besorolás rows: item id => process id and date (12 · 12.6).
     *
     * @var array<int, array{process_id: ?string, date: string}>
     */
    public array $classify = [];

    /**
     * "Válaszolok": item id => the e-mail text (22, question 1).
     *
     * @var array<int, string>
     */
    public array $replies = [];

    public ?int $replyingTo = null;

    /**
     * The Értéktükör's rows are listed under Értékmérések, not here.
     */
    protected bool $measurements = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', InboxItem::class);
    }

    public function close(int $itemId, InboxService $inbox): void
    {
        $item = InboxItem::query()->findOrFail($itemId);
        Gate::authorize('update', $item);

        $inbox->close($item, auth()->user());
        session()->flash('status', __('admin.inbox.closed_flash'));
    }

    /**
     * Besorolás: the process and the date, "Kész" (12 · 12.6). Done here or on the tab, both go.
     */
    public function classifyMaterial(int $itemId, OccasionService $occasions): void
    {
        [$item, $material] = $this->classificationItem($itemId);

        $this->validate([
            "classify.{$itemId}.process_id" => ['required', Rule::in(Process::query()->where('organization_id', $item->organization_id)->pluck('id')->map(fn ($id) => (string) $id))],
            "classify.{$itemId}.date" => ['required', 'date', 'before_or_equal:today'],
        ], attributes: [
            "classify.{$itemId}.process_id" => __('admin.occasions.process'),
            "classify.{$itemId}.date" => __('admin.occasions.date'),
        ]);

        $occasions->classify($material, Process::query()->findOrFail((int) $this->classify[$itemId]['process_id']), $this->classify[$itemId]['date'], auth()->user());
        unset($this->classify[$itemId]);
        session()->flash('status', __('admin.occasions.classified'));
    }

    public function markOther(int $itemId, OccasionService $occasions): void
    {
        [, $material] = $this->classificationItem($itemId);

        $occasions->markOther($material);
        session()->flash('status', __('admin.occasions.marked_other'));
    }

    /**
     * Feldolgozási hiba: "Újra" starts the failed work again (16, 22 · 22.2).
     */
    public function retry(int $itemId, InboxService $inbox): void
    {
        $item = $this->openItem($itemId, InboxItemType::ProcessingError);

        match (true) {
            // The capability measurement failed, not the material (NM-12).
            $item->subject instanceof Material && ($item->payload['job'] ?? null) === 'capability_detection' => DetectCapabilities::dispatch($item->subject, force: true),
            $item->subject instanceof Material => app(OccasionService::class)->retry($item->subject),
            $item->subject instanceof MainDiagnosis => DraftReading::dispatch($item->subject),
            default => abort(404),
        };

        $inbox->close($item, auth()->user());
        session()->flash('status', __('admin.inbox.retried'));
    }

    /**
     * Meghívó and Kitöltés: "Emlékeztetem", at most once in 48 hours (19 · 19.4).
     */
    public function remind(int $itemId, DiagnosisRunService $run): void
    {
        $item = InboxItem::query()->open()->whereIn('type', [InboxItemType::Invitation, InboxItemType::Completion])->findOrFail($itemId);
        Gate::authorize('update', $item);

        $sent = match (true) {
            $item->subject instanceof MainDiagnosisParticipant => $run->remindParticipant($item->subject->mainDiagnosis, $item->subject, auth()->user()),
            $item->subject instanceof MainDiagnosis => $run->remindLeader($item->subject, auth()->user()),
            default => abort(404),
        };

        $sent
            ? session()->flash('status', __('admin.inbox.reminded'))
            : session()->flash('error', __('admin.run.reminder_limit'));
    }

    /**
     * Meghívó: "Új meghívó", a fresh link (14 · 14.1); the row closes.
     */
    public function reinvite(int $itemId, MainDiagnosisService $mainDiagnoses, InboxService $inbox): void
    {
        $item = $this->openItem($itemId, InboxItemType::Invitation);
        abort_unless($item->subject instanceof MainDiagnosisParticipant, 404);

        $mainDiagnoses->resendInvitation($item->subject, auth()->user());
        $inbox->close($item, auth()->user());
        session()->flash('status', __('admin.inbox.reinvited'));
    }

    /**
     * "Válaszolok": the answer goes by e-mail, the row closes (22 · 22.2).
     */
    public function reply(int $itemId, InboxService $inbox): void
    {
        $item = $this->openItem($itemId, InboxItemType::Feedback);
        abort_unless($item->subject instanceof ReportFeedback && $item->subject->user !== null, 404);

        $this->validate(["replies.{$itemId}" => ['required', 'string', 'min:3', 'max:3000']], attributes: ["replies.{$itemId}" => __('admin.inbox.reply_text')]);

        Mail::to($item->subject->user)->send(new FeedbackReplyMail($item->subject, trim($this->replies[$itemId])));
        $item->subject->update(['answered_at' => now()]);
        $inbox->close($item, auth()->user());

        unset($this->replies[$itemId]);
        $this->replyingTo = null;
        session()->flash('status', __('admin.inbox.replied'));
    }

    public function render(): View
    {
        $showClosed = $this->filter('state') === 'closed';
        $query = InboxItem::query()
            ->with(['organization', 'diagnosis.team', 'closer'])
            ->where('is_measurement', $this->measurements)
            ->when($showClosed, fn ($query) => $query->closed(), fn ($query) => $query->open());

        if ($term = $this->searchTerm()) {
            $query->whereLike('summary', $term);
        }

        if (($type = InboxItemType::tryFrom((string) $this->filter('type'))) !== null) {
            $query->where('type', $type);
        }

        if ($organization = $this->filter('organization')) {
            $query->where('organization_id', $organization);
        }

        match ($contact = $this->filter('contact')) {
            'all' => null,
            null => $query->forContact(auth()->user()),
            default => $query->whereHas('organization', fn ($organization) => $organization->where('contact_admin_id', $contact)),
        };

        $items = $this->applySorting($query->with('subject'))->paginate(50);

        foreach ($items as $item) {
            if ($item->type === InboxItemType::Classification && $item->subject instanceof Material) {
                $this->classify[$item->id] ??= ['process_id' => null, 'date' => ($item->subject->placed_at ?? $item->created_at)->toDateString()];
            }
        }

        return view('livewire.admin.inbox.index', [
            'items' => $items,
            'processes' => Process::query()
                ->whereIn('organization_id', $items->where('type', InboxItemType::Classification)->pluck('organization_id')->filter()->unique())
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get()
                ->groupBy('organization_id'),
            'showClosed' => $showClosed,
            'title' => $this->title(),
            'types' => collect($this->types())->mapWithKeys(fn (InboxItemType $type) => [$type->value => $type->label()])->all(),
            'organizations' => Organization::query()->orderBy('name')->pluck('name', 'id')->all(),
            'contacts' => ['all' => __('admin.common.all')] + User::query()->admins()->orderBy('last_name')->get()
                ->mapWithKeys(fn (User $admin) => [$admin->id => $admin->name])->all(),
        ])->layout('layouts::admin')->title($this->title());
    }

    protected function title(): string
    {
        return __('admin.inbox.title');
    }

    /**
     * The row types of the type filter.
     *
     * @return list<InboxItemType>
     */
    protected function types(): array
    {
        return array_values(array_filter(InboxItemType::cases(), fn (InboxItemType $type) => $type !== InboxItemType::MirrorBooking));
    }

    /**
     * "Megnézem": where the row's subject is looked at.
     */
    public function viewUrl(InboxItem $item): ?string
    {
        return match (true) {
            $item->type === InboxItemType::Threshold && $item->diagnosis !== null => route('admin.main-diagnoses.run', $item->diagnosis),
            $item->type === InboxItemType::CapabilityReview && $item->diagnosis !== null => route('admin.diagnoses.capabilities', $item->diagnosis),
            $item->subject instanceof Pattern => route('admin.patterns.show', $item->subject),
            $item->subject instanceof Material => route('admin.materials.show', $item->subject),
            // "Beépítem": the reading, with the feedback next to its chapter (22 · 22.2).
            $item->subject instanceof ReportFeedback && $item->diagnosis !== null => route('admin.main-diagnoses.reading', [$item->diagnosis, 'visszajelzes' => $item->subject->id]),
            $item->subject instanceof MirrorSession && $item->subject->user !== null => route('admin.clients.show', $item->subject->user),
            $item->diagnosis !== null => route('admin.diagnoses.show', $item->diagnosis),
            default => null,
        };
    }

    private function openItem(int $itemId, InboxItemType $type): InboxItem
    {
        $item = InboxItem::query()->open()->where('type', $type)->findOrFail($itemId);
        Gate::authorize('update', $item);

        return $item;
    }

    /**
     * @return array{0: InboxItem, 1: Material}
     */
    private function classificationItem(int $itemId): array
    {
        $item = $this->openItem($itemId, InboxItemType::Classification);
        abort_unless($item->subject instanceof Material, 404);

        return [$item, $item->subject];
    }

    protected function sortableColumns(): array
    {
        return [
            'type' => 'type',
            'organization' => Organization::query()->select('name')->whereColumn('organizations.id', 'inbox_items.organization_id'),
            'age' => 'created_at',
            'closed' => 'closed_at',
        ];
    }

    protected function defaultSort(): array
    {
        return ['age', 'asc'];
    }
}
