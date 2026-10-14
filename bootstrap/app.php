<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\SeoController;
use App\Http\Middleware\AddRobotsHeader;
use App\Http\Middleware\AssignLogId;
use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Middleware\AuthenticateCustomer;
use App\Http\Middleware\EnsureLegalAcceptance;
use App\Http\Middleware\EnsureSuperadmin;
use App\Http\Middleware\ForbidWhileImpersonating;
use App\Http\Middleware\ProtectStaging;
use App\Http\Middleware\ResolveOrganization;
use App\Services\Alerting\ErrorAlerter;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        using: function (): void {
            // Routes without the web group: no session, cookies or CSRF, so the
            // uptime monitor and crawlers stay cheap (docs/uzemeltetes.md · 3).
            // They come first, so the /{organization} pages cannot swallow them.
            Route::get('/health', HealthController::class)->name('health');
            Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
            Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

            Route::middleware('web')->group(base_path('routes/web.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(AssignLogId::class);
        $middleware->append(AddRobotsHeader::class);
        $middleware->web(prepend: [ProtectStaging::class]);

        $middleware->alias([
            'admin' => AuthenticateAdmin::class,
            'customer' => AuthenticateCustomer::class,
            'superadmin' => EnsureSuperadmin::class,
            'organization' => ResolveOrganization::class,
            'legal' => EnsureLegalAcceptance::class,
            'not-impersonating' => ForbidWhileImpersonating::class,
        ]);

        // Login pages send an already logged-in user to where they belong.
        $middleware->redirectUsersTo(fn (Request $request): string => $request->is('admin*')
            ? route('admin.dashboard')
            : route('client.landing'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Reported exceptions during an HTTP request end in a 500; queued jobs
        // alert only once their retries are exhausted (AppServiceProvider).
        $exceptions->report(function (Throwable $exception): void {
            $request = request();

            if ($request->attributes->has(AssignLogId::REQUEST_ATTRIBUTE)) {
                app(ErrorAlerter::class)->reportException($exception, $request->url());
            }
        });
    })->create();
