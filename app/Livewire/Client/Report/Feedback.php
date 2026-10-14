<?php

namespace App\Livewire\Client\Report;

use App\Enums\InboxItemType;
use App\Enums\MainDiagnosisStage;
use App\Livewire\Concerns\WithDictation;
use App\Models\MainDiagnosis;
use App\Models\Organization;
use App\Models\ReportFeedback;
use App\Services\Auth\ImpersonationService;
use App\Services\Inbox\InboxService;
use App\Services\Report\ReportService;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * R2·c · Magatokra ismertek? (28 · 28.1): Igen · Részben · Nem így látjuk,
 * "Miben nem?" written or spoken. Részben and Nem go to the Beérkező; the
 * latest answer counts, and nothing changes a score.
 */
class Feedback extends Component
{
    use WithDictation, WithFileUploads;

    public MainDiagnosis $main;

    public string $chapter = '';

    public ?string $answer = null;

    public string $text = '';

    public function mount(MainDiagnosis $main, string $chapter): void
    {
        $this->main = $main;
        $this->chapter = $chapter;
    }

    public function choose(string $answer): void
    {
        $this->authorizeFeedback();
        abort_unless(in_array($answer, ReportFeedback::ANSWERS, true), 404);

        $this->answer = $answer;
        $this->resetValidation();

        // "Igen" is sent at once; it is counted on the diagnosis, not in the Beérkező (28, question 2).
        if ($answer === 'yes') {
            $this->store();
        }
    }

    public function send(): void
    {
        $this->authorizeFeedback();

        $this->validate([
            'answer' => ['required', Rule::in(['partial', 'no'])],
            'text' => ['required', 'string', 'min:3', 'max:3000'],
        ], attributes: ['text' => __('report.feedback.what')]);

        $this->store();
    }

    public function render(): View
    {
        return view('livewire.client.report.feedback', [
            'latest' => ReportFeedback::query()->where('main_diagnosis_id', $this->main->id)->where('chapter', $this->chapter)->where('user_id', auth('web')->id())->latest('id')->first(),
            'open' => $this->main->stage === MainDiagnosisStage::Published,
        ]);
    }

    private function store(): void
    {
        $user = auth('web')->user();
        $feedback = ReportFeedback::create([
            'main_diagnosis_id' => $this->main->id,
            'user_id' => $user->id,
            'chapter' => $this->chapter,
            'answer' => $this->answer,
            'text' => $this->answer === 'yes' ? null : trim($this->text),
            'answered_at' => now(),
        ]);

        if ($this->answer !== 'yes') {
            app(InboxService::class)->open(
                InboxItemType::Feedback,
                __('report.feedback.summary', [
                    'name' => $user->name,
                    'chapter' => __('report.chapters.'.$this->chapter),
                    'answer' => __('report.feedback.answers.'.$this->answer),
                    'text' => mb_substr($feedback->text, 0, 200),
                ]),
                diagnosis: $this->main->diagnosis,
                subject: $feedback,
            );
        }

        $this->reset(['answer', 'text']);
    }

    /**
     * Until the diagnosis is closed, by those who see the report, never in their name (28, question 3).
     */
    private function authorizeFeedback(): void
    {
        abort_unless($this->main->stage === MainDiagnosisStage::Published, 403);
        abort_unless(app(ReportService::class)->canView(auth('web')->user(), $this->main), 403);
        abort_if(app(ImpersonationService::class)->isImpersonating(), 403, __('impersonation.forbidden'));
    }

    protected function canDictate(string $field): bool
    {
        return $field === 'text' && $this->main->stage === MainDiagnosisStage::Published && ! app(ImpersonationService::class)->isImpersonating();
    }

    protected function dictationOrganization(): ?Organization
    {
        return $this->main->diagnosis->organization;
    }
}
