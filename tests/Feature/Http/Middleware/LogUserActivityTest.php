<?php

use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord;

beforeEach(function () {
    foreach ([ActivityLogger::ADMIN_CHANNEL, ActivityLogger::CUSTOMER_CHANNEL] as $channel) {
        config(["logging.channels.{$channel}" => ['driver' => 'monolog', 'handler' => TestHandler::class]]);
    }
});

/**
 * @return list<LogRecord>
 */
function activityRecords(string $channel): array
{
    return Log::channel($channel)->getLogger()->getHandlers()[0]->getRecords();
}

/**
 * @return list<string>
 */
function activityMessages(string $channel): array
{
    return array_map(fn (LogRecord $record): string => $record->message, activityRecords($channel));
}

/**
 * A Livewire update of one component, sent to a test route of the web group.
 *
 * @param  list<array{method: string}>  $calls
 */
function livewireUpdate(string $component, string $pagePath, array $calls): TestResponse
{
    return test()->post('/_test/livewire', ['components' => [[
        'snapshot' => json_encode(['memo' => ['name' => $component, 'path' => $pagePath]]),
        'updates' => [],
        'calls' => $calls,
    ]]], ['X-Livewire' => '1']);
}

it('logs the pages an admin opens into the admin file', function () {
    $admin = actingAsAdmin();

    $this->get(route('admin.organizations.index'))->assertOk();

    $record = collect(activityRecords(ActivityLogger::ADMIN_CHANNEL))->sole();
    expect($record->message)->toBe('GET admin.organizations.index')
        ->and($record->context)->toMatchArray([
            'user_id' => $admin->id,
            'email' => $admin->email,
            'method' => 'GET',
            'route' => 'admin.organizations.index',
            'path' => '/admin/szervezetek',
            'status' => 200,
        ])
        ->and(activityRecords(ActivityLogger::CUSTOMER_CHANNEL))->toBe([]);
});

it('logs the pages a customer opens into the customer file', function () {
    $organization = Organization::factory()->create();
    $customer = actingAsCustomer(customerOf($organization));

    $this->get(route('client.home', $organization))->assertOk();

    $record = collect(activityRecords(ActivityLogger::CUSTOMER_CHANNEL))->sole();
    expect($record->message)->toBe('GET client.home')
        ->and($record->context)->toMatchArray(['user_id' => $customer->id, 'email' => $customer->email])
        ->and(activityRecords(ActivityLogger::ADMIN_CHANNEL))->toBe([]);
});

it('logs the login, which starts without a user, and the logout, which ends without one', function () {
    fakeTurnstile();
    $admin = User::factory()->admin()->create(['password' => Hash::make('correct-horse-battery')]);

    $this->post(route('admin.login.store'), [
        'email' => $admin->email,
        'password' => 'correct-horse-battery',
        'cf-turnstile-response' => 'turnstile-token',
    ])->assertRedirect(route('admin.dashboard'));
    $this->post(route('admin.logout'))->assertRedirect();

    expect(activityMessages(ActivityLogger::ADMIN_CHANNEL))->toBe(['POST admin.login.store', 'POST admin.logout'])
        ->and(collect(activityRecords(ActivityLogger::ADMIN_CHANNEL))->map(fn (LogRecord $record) => $record->context['user_id'])->all())
        ->toBe([$admin->id, $admin->id]);
});

it('logs the work of an admin in a customer’s name into both files', function () {
    $admin = actingAsAdmin();
    $organization = Organization::factory()->create();
    $customer = customerOf($organization);

    $this->post(route('admin.impersonate', $customer));
    $this->get(route('client.home', $organization))->assertOk();

    $inTheirName = [
        'user_id' => $admin->id,
        'email' => $admin->email,
        'on_behalf_of_id' => $customer->id,
        'on_behalf_of_email' => $customer->email,
    ];
    expect(activityMessages(ActivityLogger::ADMIN_CHANNEL))->toBe(['POST admin.impersonate', 'GET client.home'])
        ->and(activityRecords(ActivityLogger::ADMIN_CHANNEL)[1]->context)->toMatchArray($inTheirName)
        ->and(activityMessages(ActivityLogger::CUSTOMER_CHANNEL))->toBe(['GET client.home'])
        ->and(activityRecords(ActivityLogger::CUSTOMER_CHANNEL)[0]->context)->toMatchArray($inTheirName);
});

it('logs the actions of a Livewire update with its page, but not Livewire’s own calls or the polls', function () {
    Route::middleware('web')->post('/_test/livewire', fn () => 'ok');
    $admin = actingAsAdmin();

    livewireUpdate('admin.clients.show', 'admin/ugyfelek/5', [['method' => 'save'], ['method' => '$refresh'], ['method' => '_startUpload']])->assertOk();
    livewireUpdate('admin.organizations.processes', 'admin/szervezetek/5', [['method' => 'pollDictations'], ['method' => '$refresh']])->assertOk();

    $record = collect(activityRecords(ActivityLogger::ADMIN_CHANNEL))->sole();
    expect($record->message)->toBe('Livewire admin.clients.show::save')
        ->and($record->context)->toMatchArray(['user_id' => $admin->id, 'method' => 'POST', 'path' => '/admin/ugyfelek/5'])
        ->and($record->context)->not->toHaveKey('route');
});

it('logs a field saved when it changed as an action of its Livewire update', function () {
    Route::middleware('web')->post('/_test/livewire', function () {
        app(ActivityLogger::class)->noteChange('client.profile::last_name');

        return 'ok';
    });
    actingAsCustomer(customerOf());

    livewireUpdate('client.profile', 'acme/profil', [])->assertOk();

    expect(activityMessages(ActivityLogger::CUSTOMER_CHANNEL))->toBe(['Livewire client.profile::last_name']);
});

it('keeps the token of a link out of the logged path', function () {
    actingAsCustomer(customerOf());

    $this->get(route('client.login.link', 'titkos-token-123'));

    $record = collect(activityRecords(ActivityLogger::CUSTOMER_CHANNEL))->sole();
    expect($record->message)->toBe('GET client.login.link')
        ->and($record->context['path'])->toBe('/belepes/link/[redacted]');
});

it('logs nothing for visitors who are not logged in', function () {
    $this->get(route('public.home'))->assertOk();
    $this->get(route('admin.login'))->assertOk();

    expect(activityRecords(ActivityLogger::ADMIN_CHANNEL))->toBe([])
        ->and(activityRecords(ActivityLogger::CUSTOMER_CHANNEL))->toBe([]);
});
