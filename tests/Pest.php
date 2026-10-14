<?php

use App\Enums\MainDiagnosisSubject;
use App\Models\MainDiagnosis;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\OrganizationValueSet;
use App\Models\PersonalValueSet;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;
use App\Services\Auth\GuardSession;
use App\Services\MainDiagnoses\MainDiagnosisService;
use App\Services\Organizations\OrganizationValueSetService;
use App\Services\Values\PersonalValueSetService;
use App\Services\Values\ValueService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature');

// Browser tests load the built assets, so layout and contrast are checked for real (npm run build first).
pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->beforeEach(fn () => $this->withVite())
    ->in('Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Logs an admin in on the admin guard the way a real login does: the session
 * carries the current auth epoch and a fresh activity time.
 */
function actingAsAdmin(?User $admin = null): User
{
    $admin ??= User::factory()->admin()->create();

    test()->actingAs($admin, GuardSession::ADMIN)->withSession([
        'auth_epoch.'.GuardSession::ADMIN => $admin->auth_epoch,
        GuardSession::ADMIN_LAST_ACTIVITY_KEY => now()->getTimestamp(),
    ]);

    return $admin;
}

/**
 * Logs a customer in on the customer guard, with the session's auth epoch.
 */
function actingAsCustomer(User $customer): User
{
    test()->actingAs($customer, GuardSession::WEB)->withSession([
        'auth_epoch.'.GuardSession::WEB => $customer->auth_epoch,
    ]);

    return $customer;
}

/**
 * An activated customer with an active membership in an active organization.
 */
function customerOf(?Organization $organization = null): User
{
    $organization ??= Organization::factory()->create();
    $customer = User::factory()->create();

    Membership::factory()->for($organization)->for($customer)->create();

    return $customer;
}

/**
 * One of the starting products (02 · 2.1) with its diagnosis flow (17 · 17.3).
 */
function startingProduct(string $key = Product::FULL): Product
{
    return Product::query()->where('key', $key)->first() ?? Product::factory()->starting($key)->create();
}

/**
 * The organization's value set with 3 values in its own words and the brand
 * base, saved as a version (08).
 */
function organizationValues(Organization $organization, int $count = 3, bool $withBrand = true): OrganizationValueSet
{
    $service = app(OrganizationValueSetService::class);
    $set = $service->forOrganization($organization);
    $items = collect(range(1, $count))->map(function (int $index) use ($service) {
        $value = app(ValueService::class)->create([
            'name' => 'Érték '.$index.' '.Str::random(4),
            'meanings' => ['Jelentés '.$index],
            'low_examples' => ['Alig '.$index],
            'high_examples' => ['Nagyon '.$index],
        ]);

        return $service->itemFromValue($value->load('latestVersion'));
    })->all();

    $service->save($set, $items, $withBrand
        ? ['purpose' => 'Miért', 'value_proposition' => 'Mit kap', 'impact' => 'Mi marad']
        : []);

    return $set->refresh();
}

/**
 * A team of an organization with the given product: a leader (by default the
 * organization's leader too), peers, a contact admin and the value set (17 · 17.2).
 *
 * @return array{organization: Organization, team: Team, leader: User, peers: list<User>, admin: User}
 */
function diagnosisTeam(string $product = Product::FULL, int $peers = 3, bool $organizationLeader = true, bool $withValues = true): array
{
    $admin = User::factory()->admin()->create();
    $organization = Organization::factory()->create([
        'product_id' => startingProduct($product)->id,
        'contact_admin_id' => $admin->id,
    ]);
    $team = Team::factory()->for($organization)->create();
    $leader = User::factory()->create();
    Membership::factory()->for($organization)->for($leader)->create(['is_organization_leader' => $organizationLeader]);
    $team->users()->attach($leader->id, ['is_leader' => true]);

    $peerUsers = collect(range(1, $peers))->map(function () use ($organization, $team) {
        $peer = User::factory()->create();
        Membership::factory()->for($organization)->for($peer)->create();
        $team->users()->attach($peer->id, ['is_leader' => false]);

        return $peer;
    })->all();

    if ($withValues) {
        organizationValues($organization);
    }

    return ['organization' => $organization, 'team' => $team, 'leader' => $leader, 'peers' => $peerUsers, 'admin' => $admin];
}

/**
 * A main diagnosis of the team, invited unless asked otherwise (17 · 17.2).
 *
 * @param  array{organization: Organization, team: Team, leader: User, admin: User}  $setup
 * @param  array<string, mixed>  $overrides
 */
function startMainDiagnosis(array $setup, bool $invite = true, array $overrides = []): MainDiagnosis
{
    Mail::fake();

    $service = app(MainDiagnosisService::class);
    $main = $service->create([
        'organization_id' => $setup['organization']->id,
        'subject' => MainDiagnosisSubject::Team->value,
        'team_id' => $setup['team']?->id,
        'leader_id' => $setup['leader']->id,
        'starts_on' => today()->addWeek()->toDateString(),
        'publishes_on' => today()->addWeeks(6)->toDateString(),
        'show_peer_quotes' => true,
        ...$overrides,
    ], $setup['admin']);

    if ($invite) {
        $service->invite($main, $setup['admin']);
    }

    return $main->refresh();
}

/**
 * The leader's own values, confirmed with "Tovább" (24).
 */
function confirmOwnValues(User $user, int $count = 1): PersonalValueSet
{
    $service = app(PersonalValueSetService::class);
    $set = $service->forUser($user);
    $items = collect(range(1, $count))->map(fn (int $index) => [
        'value_id' => null,
        'name' => 'Saját '.$index.' '.Str::random(4),
        'meaning' => 'Ezt jelenti '.$index,
        'low_example' => 'Alig',
        'high_example' => 'Nagyon',
        'source' => 'new',
    ])->all();

    $service->save($set, $items, recordVersion: true);

    return $set->refresh();
}

/**
 * Cloudflare's verification answers with the given result.
 */
function fakeTurnstile(bool $success = true): void
{
    Http::fake([
        'https://challenges.cloudflare.com/*' => Http::response(['success' => $success]),
        'https://api.pwnedpasswords.com/*' => Http::response(''),
    ]);
}

/**
 * Browser tests: fills the field the Turnstile widget would add, so the flow
 * does not depend on Cloudflare being reachable; the server check is faked.
 */
function passTurnstile(mixed $page): void
{
    $page->script(<<<'JS'
        () => document.querySelector('form').insertAdjacentHTML(
            'beforeend',
            '<input type="hidden" name="cf-turnstile-response" value="test-token">'
        )
    JS);
}
