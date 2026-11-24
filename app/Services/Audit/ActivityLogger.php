<?php

namespace App\Services\Audit;

use App\Logging\RedactSensitiveData;
use App\Models\User;
use App\Services\Auth\GuardSession;
use App\Services\Auth\ImpersonationService;
use App\Support\LivewireUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Which user did what and when, in a daily file (docs/uzemeltetes.md · 4):
 * the pages they opened, the forms they sent and the Livewire actions they
 * called. Admins and customers write separate files; the work of an admin
 * in a customer's name goes into both. Visitors who are not logged in are
 * not logged here.
 */
class ActivityLogger
{
    public const ADMIN_CHANNEL = 'activity-admin';

    public const CUSTOMER_CHANNEL = 'activity-customer';

    /**
     * Route parameters kept out of the logged path, such as login links.
     */
    private const SECRET_PARAMETERS = ['token'];

    /**
     * The fields saved when they changed during this request.
     *
     * @var list<string>
     */
    private array $changes = [];

    public function __construct(private ImpersonationService $impersonation) {}

    /**
     * Who acts in the request: the admin on the admin side; on the customer
     * side the customer, or the admin working in the customer's name.
     *
     * @return array{actor: User, on_behalf_of: User|null}|null
     */
    public function actorsOf(Request $request): ?array
    {
        if ($this->isAdminArea($this->pagePath($request))) {
            $admin = Auth::guard(GuardSession::ADMIN)->user();

            return $admin instanceof User ? ['actor' => $admin, 'on_behalf_of' => null] : null;
        }

        $customer = Auth::guard(GuardSession::WEB)->user();

        if (! $customer instanceof User) {
            return null;
        }

        $admin = $this->impersonation->current()?->admin;

        return $admin instanceof User
            ? ['actor' => $admin, 'on_behalf_of' => $customer]
            : ['actor' => $customer, 'on_behalf_of' => null];
    }

    /**
     * A field that saves when it changes: an action of its Livewire update.
     */
    public function noteChange(string $change): void
    {
        $this->changes[] = $change;
    }

    /**
     * Writes the request once it ran. The actors are read before it, as a
     * logout ends with nobody logged in; a login starts with nobody, so
     * then they are read after it.
     *
     * @param  array{actor: User, on_behalf_of: User|null}|null  $actors
     */
    public function record(Request $request, Response $response, ?array $actors): void
    {
        $changes = $this->changes;
        $this->changes = [];
        $actors ??= $this->actorsOf($request);
        $action = $this->actionOf($request, $changes);

        if ($actors === null || $action === null) {
            return;
        }

        $context = array_filter([
            'user_id' => $actors['actor']->id,
            'email' => $actors['actor']->email,
            'on_behalf_of_id' => $actors['on_behalf_of']?->id,
            'on_behalf_of_email' => $actors['on_behalf_of']?->email,
            'method' => $request->method(),
            'route' => $this->isLivewireUpdate($request) ? null : $request->route()?->getName(),
            'path' => $this->loggedPath($request),
            'status' => $response->getStatusCode(),
            'ip' => $request->ip(),
        ], fn ($value): bool => $value !== null);

        foreach ($this->channelsOf($actors) as $channel) {
            Log::channel($channel)->info($action, $context);
        }
    }

    /**
     * "GET admin.clients.show" for a page or a form, "Livewire
     * client.profile::save" for the actions of a Livewire update. A Livewire
     * update that only refreshed or polled did nothing; file uploads are
     * logged with the action that saves them.
     *
     * @param  list<string>  $changes
     */
    private function actionOf(Request $request, array $changes): ?string
    {
        if ($this->isLivewireUpdate($request)) {
            $actions = array_values(array_unique([...LivewireUpdate::actions($this->components($request)), ...$changes]));

            return $actions === [] ? null : 'Livewire '.implode(', ', $actions);
        }

        $routeName = $request->route()?->getName();

        if (is_string($routeName) && str_contains($routeName, 'livewire.')) {
            return null;
        }

        return $request->method().' '.($routeName ?? $this->loggedPath($request));
    }

    /**
     * @param  array{actor: User, on_behalf_of: User|null}  $actors
     * @return list<string>
     */
    private function channelsOf(array $actors): array
    {
        return collect([$actors['actor'], $actors['on_behalf_of']])
            ->filter()
            ->map(fn (User $user): string => $user->isAdmin() ? self::ADMIN_CHANNEL : self::CUSTOMER_CHANNEL)
            ->unique()
            ->values()
            ->all();
    }

    private function loggedPath(Request $request): string
    {
        $path = '/'.$this->pagePath($request);

        foreach (self::SECRET_PARAMETERS as $parameter) {
            $value = $request->route()?->originalParameter($parameter);

            if (is_string($value) && $value !== '') {
                $path = str_replace($value, RedactSensitiveData::MASK, $path);
            }
        }

        return $path;
    }

    /**
     * The page the request belongs to: for a Livewire update, the page its
     * components were loaded on.
     */
    private function pagePath(Request $request): string
    {
        $path = $this->isLivewireUpdate($request)
            ? LivewireUpdate::pagePath($this->components($request))
            : $request->path();

        return $path === '/' ? '' : ltrim($path, '/');
    }

    private function isAdminArea(string $path): bool
    {
        return $path === 'admin' || str_starts_with($path, 'admin/');
    }

    private function isLivewireUpdate(Request $request): bool
    {
        return $request->hasHeader('X-Livewire');
    }

    /**
     * @return array<array-key, mixed>
     */
    private function components(Request $request): array
    {
        $components = $request->input('components', []);

        return is_array($components) ? $components : [];
    }
}
