<?php

namespace App\Livewire\Client\Diagnosis;

use App\Enums\DiagnosisStep;
use App\Livewire\Client\Diagnosis\Concerns\InteractsWithMainDiagnosis;
use App\Models\CalendarConnection;
use App\Models\Diagnosis;
use App\Models\Organization;
use App\Services\Calendar\CalendarException;
use App\Services\Calendar\CalendarService;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * V6·b · Naptár (09 · 9.1, 9.2): what we read and what never, then the
 * source: Google, Microsoft 365 / Outlook, an iCal link or an ICS file.
 * A failure offers the conversation instead (09 · 9.6).
 */
class ProcessCalendar extends Component
{
    use InteractsWithMainDiagnosis, WithFileUploads;

    public bool $choosing = false;

    public string $icsUrl = '';

    /** @var TemporaryUploadedFile|null */
    public $icsFile = null;

    public ?string $failure = null;

    public function mount(Organization $organization, Diagnosis $diagnosis): void
    {
        $this->bootDiagnosis($organization, $diagnosis);
        abort_unless($this->isLeader(), 403);

        $this->failure = session('calendar_error');
        $this->choosing = $this->failure !== null;
    }

    public function connectUrl(CalendarService $calendars): void
    {
        $this->authorizeConnect();
        $this->validate(['icsUrl' => ['required', 'string', 'max:2000', 'regex:/^(https|webcal):\/\//i']], attributes: [
            'icsUrl' => __('calendar.ics_url'),
        ]);

        $this->run(fn () => $calendars->connectIcsUrl($this->user(), $this->organization, $this->main(), trim($this->icsUrl)));
    }

    public function connectFile(CalendarService $calendars): void
    {
        $this->authorizeConnect();
        $this->validate(['icsFile' => ['required', 'file', 'max:'.config('megis.calendar.ics_max_kilobytes'), 'extensions:ics']], attributes: [
            'icsFile' => __('calendar.ics_file'),
        ]);

        $contents = (string) file_get_contents($this->icsFile->getRealPath());
        $this->icsFile = null;

        $this->run(fn () => $calendars->connectIcsFile($this->user(), $this->organization, $this->main(), $contents));
    }

    public function render(): View
    {
        return view('livewire.client.diagnosis.process-calendar', [
            'providers' => collect([CalendarConnection::GOOGLE, CalendarConnection::MICROSOFT])
                ->filter(fn (string $provider) => filled(config('services.'.$provider.'.client_id')) && filled(config('services.'.$provider.'.client_secret')))
                ->values(),
            'connection' => CalendarConnection::query()->whereBelongsTo($this->organization)->where('user_id', $this->user()->id)->where('status', CalendarConnection::ACTIVE)->latest('id')->first(),
            'canConnect' => $this->acceptsInput() && ! $this->isImpersonating(),
            'maxMegabytes' => (int) (config('megis.calendar.ics_max_kilobytes') / 1024),
        ])->layout('layouts::client', [
            ...$this->stepLayout(DiagnosisStep::Processes, __('calendar.lead')),
            'title' => __('calendar.title'),
            'back' => route('client.diagnosis.folyamatok', [$this->organization, $this->diagnosis]),
            'backLabel' => DiagnosisStep::Processes->label(),
        ])->title(__('calendar.title'));
    }

    private function run(callable $connect): void
    {
        try {
            $connect();
        } catch (CalendarException $exception) {
            $this->failure = $exception->getMessage();

            return;
        }

        $this->redirect(route('client.diagnosis.process-list', [$this->organization, $this->diagnosis]), navigate: false);
    }

    /**
     * The user connects their own calendar, never an admin in their name (14 · 14.6).
     */
    private function authorizeConnect(): void
    {
        $this->authorizeInput();
        abort_if($this->isImpersonating(), 403, __('impersonation.forbidden'));
    }
}
