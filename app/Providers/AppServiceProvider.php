<?php

namespace App\Providers;

use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Middleware\AuthenticateCustomer;
use App\Http\Middleware\EnsureLegalAcceptance;
use App\Http\Middleware\EnsureSuperadmin;
use App\Http\Middleware\ForbidWhileImpersonating;
use App\Http\Middleware\ResolveOrganization;
use App\Models\User;
use App\Services\Alerting\ErrorAlerter;
use App\Services\Auth\ImpersonationService;
use App\Support\CurrentOrganization;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CurrentOrganization::class);
        $this->app->scoped(ImpersonationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        CarbonImmutable::setLocale(config('app.locale'));

        $this->reportFailedJobs();
        $this->configureRateLimiting();
        $this->configureGates();

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
