<?php

namespace App\Providers;

use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Middleware\AuthenticateCustomer;
use App\Http\Middleware\EnsureLegalAcceptance;
use App\Http\Middleware\EnsureSuperadmin;
use App\Http\Middleware\ForbidWhileImpersonating;
use App\Http\Middleware\ResolveOrganization;
use App\Models\CalendarConnection;
use App\Models\Capability;
use App\Models\Diagnosis;
use App\Models\DiagnosisClaim;
use App\Models\DiagnosisRow;
use App\Models\DiagnosisText;
use App\Models\EvidenceTrace;
use App\Models\MainDiagnosis;
use App\Models\MainDiagnosisParticipant;
use App\Models\Material;
use App\Models\MirrorSession;
use App\Models\OrganizationValueSet;
use App\Models\Pattern;
use App\Models\PersonalValueSet;
use App\Models\ProcessConversation;
use App\Models\ReportFeedback;
use App\Models\User;
use App\Models\Value;
use App\Services\Alerting\ErrorAlerter;
use App\Services\Auth\ImpersonationService;
use App\Services\Transcription\AiTranscriber;
use App\Services\Transcription\FakeTranscriber;
use App\Services\Transcription\Transcriber;
use App\Support\CurrentOrganization;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Mail\Markdown;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider as MicrosoftProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CurrentOrganization::class);
        $this->app->scoped(ImpersonationService::class);

        // The fake transcriber is for local work only, never in production.
        $this->app->bind(Transcriber::class, fn ($app) => config('megis.transcriber') === 'fake' && ! $app->isProduction()
            ? new FakeTranscriber
            : $app->make(AiTranscriber::class));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        CarbonImmutable::setLocale(config('app.locale'));

        // Outside production a lazy-loaded relation of a model from a list fails loudly:
        // that is an N+1 query (NFR · 6). In production it only costs a query.
        Model::preventLazyLoading(! $this->app->isProduction());

        // On staging the mégis walks the app: an N+1 there is logged, not thrown.
        if ($this->app->environment('staging')) {
            Model::handleLazyLoadingViolationUsing(fn (Model $model, string $relation) => Log::warning('Lazy loaded relation (N+1).', [
                'model' => $model::class,
                'relation' => $relation,
            ]));
        }

        // Names, quotes and answers in a markdown e-mail stay text: "[x](link)"
        // in someone's name does not become a link in a mail to someone else.
        Markdown::withSecuredEncoding();

        $this->reportFailedJobs();
        $this->configureRateLimiting();
        $this->configureGates();

        // Microsoft 365 / Outlook calendars through Socialite (09 · 9.2).
        Event::listen(fn (SocialiteWasCalled $event) => $event->extendSocialite('microsoft', MicrosoftProvider::class));

        // Stable names in polymorphic columns (versions, inbox), independent of class names.
        Relation::enforceMorphMap([
            'value' => Value::class,
            'capability' => Capability::class,
            'pattern' => Pattern::class,
            'organization_value_set' => OrganizationValueSet::class,
            'diagnosis' => Diagnosis::class,
            'material' => Material::class,
            'mirror_session' => MirrorSession::class,
            'main_diagnosis' => MainDiagnosis::class,
            'personal_value_set' => PersonalValueSet::class,
            'process_conversation' => ProcessConversation::class,
            'calendar_connection' => CalendarConnection::class,
            'main_diagnosis_participant' => MainDiagnosisParticipant::class,
            'diagnosis_row' => DiagnosisRow::class,
            'diagnosis_claim' => DiagnosisClaim::class,
            'diagnosis_text' => DiagnosisText::class,
            'evidence_trace' => EvidenceTrace::class,
            'report_feedback' => ReportFeedback::class,
        ]);

        // Livewire update requests run these again, so actions are checked like page loads.
        Livewire::addPersistentMiddleware([
            AuthenticateAdmin::class,
            AuthenticateCustomer::class,
            EnsureSuperadmin::class,
            ResolveOrganization::class,
            EnsureLegalAcceptance::class,
            ForbidWhileImpersonating::class,
        ]);
    }

    /**
     * A job alerts only after its last retry failed (docs/uzemeltetes.md · 5).
     */
    private function reportFailedJobs(): void
    {
        Queue::failing(function (JobFailed $event): void {
            $jobName = $event->job->resolveName();

            Log::channel('queue')->error('Job failed after its last attempt.', [
                'job' => $jobName,
                'queue' => $event->job->getQueue(),
                'error' => $event->exception::class,
            ]);

            $this->app->make(ErrorAlerter::class)->reportFailedJob($jobName, $event->exception);
        });
    }

    /**
     * Public forms are limited by IP. Login limits live in the controllers
     * (ThrottlesAttempts), so they can be shown as form errors.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('public-form', fn (Request $request) => Limit::perMinute(10)->by((string) $request->ip()));
    }

    private function configureGates(): void
    {
        Gate::define('viewSystem', fn (User $user): bool => $user->isSuperadmin());
    }
}
