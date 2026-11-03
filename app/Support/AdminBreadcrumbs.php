<?php

namespace App\Support;

use App\Models\Diagnosis;
use App\Models\Material;
use App\Models\Organization;
use App\Models\Process;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * The breadcrumbs of an admin page (AdminLocation): the pages above it up to
 * its menu item. Inside an organization they run through its tabs: a
 * diagnosis sits under the Diagnózisok tab, a subdiagnosis under its main
 * diagnosis' Részdiagnózisok; every other page under the list of its menu
 * item (AdminNavigation).
 *
 * Only the layout asks, on the full page request: the route's parameters are
 * models by then, and a Livewire update does not render the layout again.
 */
class AdminBreadcrumbs
{
    /**
     * The tabs of an organization (02 · 2.3), in the layout's header.
     */
    private const ORGANIZATION_TABS = [
        'admin.organizations.edit',
        'admin.organizations.members',
        'admin.organizations.teams',
        'admin.organizations.values',
        'admin.organizations.brand',
        'admin.organizations.processes',
        'admin.organizations.diagnoses',
    ];

    /**
     * The tabs of a main diagnosis (x-admin.main-diagnosis-tabs).
     */
    private const MAIN_DIAGNOSIS_TABS = [
        'admin.diagnoses.show',
        'admin.main-diagnoses.edit',
        'admin.main-diagnoses.run',
        'admin.main-diagnoses.reading',
        'admin.main-diagnoses.publish',
        'admin.main-diagnoses.subdiagnoses',
        'admin.diagnoses.capabilities',
    ];

    /**
     * Library pages whose tabs hang under their first one: route => [parameter, home route].
     */
    private const LIBRARY_HOMES = [
        'admin.values.edit' => ['value', 'admin.values.edit'],
        'admin.values.versions' => ['value', 'admin.values.edit'],
        'admin.capabilities.edit' => ['capability', 'admin.capabilities.edit'],
        'admin.capabilities.patterns' => ['capability', 'admin.capabilities.edit'],
        'admin.capabilities.versions' => ['capability', 'admin.capabilities.edit'],
        'admin.patterns.show' => ['pattern', 'admin.patterns.show'],
        'admin.patterns.versions' => ['pattern', 'admin.patterns.show'],
    ];

    public function __construct(private readonly AdminNavigation $navigation) {}

    public function current(): AdminLocation
    {
        $route = request()->route();

        if ($route?->getName() === null || Livewire::isLivewireRequest()) {
            return new AdminLocation;
        }

        return $this->resolve($route->getName(), $route->parameters(), request()->query());
    }

    /**
     * The crumbs as the layout draws them. Inside an organization the first
     * two, Szervezetek and the organization, head the page in the header; the
     * rest open the content, above the title. The crumb of the page the admin
     * came from leads back to it as it was left (AdminBackLink), with the
     * list's filters and page; from anywhere else a Vissza leads there.
     *
     * @return array{header: list<array{label: string, url: string}>, page: list<array{label: string, url: string}>, back: ?string}
     */
    public function links(AdminLocation $location): array
    {
        $trail = $location->trail;
        $back = null;

        if ($trail !== []) {
            $path = fn (string $url): string => trim((string) parse_url($url, PHP_URL_PATH), '/');
            $origin = app(AdminBackLink::class)->url($location->parent()['url'], $location->home);
            $originCrumb = collect($trail)->search(fn (array $crumb) => $path($crumb['url']) === $path($origin));

            if ($originCrumb === false) {
                $back = $origin;
            } else {
                $trail[$originCrumb]['url'] = $origin;
            }
        }

        $inHeader = $location->organization === null ? 0 : count($this->organizationCrumbs($location->organization));

        return [
            'header' => array_slice($trail, 0, $inHeader),
            'page' => array_slice($trail, $inHeader),
            'back' => $back,
        ];
    }

    /**
     * @param  array<string, mixed>  $parameters  the route's parameters, models or keys
     * @param  array<string, mixed>  $query
     */
    public function resolve(string $routeName, array $parameters = [], array $query = []): AdminLocation
    {
        $organization = $this->model(Organization::class, $parameters['organization'] ?? null);
        $diagnosis = $this->model(Diagnosis::class, $parameters['diagnosis'] ?? null);

        return match (true) {
            $diagnosis !== null => $this->diagnosisPage($routeName, $diagnosis),
            $routeName === 'admin.materials.show' => $this->materialPage($this->model(Material::class, $parameters['material'] ?? null)),
            $organization !== null => $this->organizationPage($routeName, $organization, $this->model(Process::class, $parameters['process'] ?? null)),
            $routeName === 'admin.main-diagnoses.create' && filled($query['szervezet'] ?? null) => $this->startPage($this->model(Organization::class, $query['szervezet']))
                ?? $this->listPage($routeName),
            default => $this->listPage($routeName, $parameters),
        };
    }

    /**
     * The tabs of an organization sit under it; the pages under a tab (a
     * process' occasions, a member's form) under the tab.
     */
    private function organizationPage(string $routeName, Organization $organization, ?Process $process): AdminLocation
    {
        if (in_array($routeName, self::ORGANIZATION_TABS, true)) {
            return $this->inOrganization($organization, $routeName, $this->organizationCrumbs($organization), home: route('admin.organizations.edit', $organization));
        }

        $tab = $process !== null ? 'admin.organizations.processes' : 'admin.organizations.members';

        return $this->inOrganization($organization, $tab, $this->tabCrumbs($organization, $tab));
    }

    /**
     * A1 opened from an organization's Diagnózisok tab.
     */
    private function startPage(?Organization $organization): ?AdminLocation
    {
        return $organization === null ? null : $this->inOrganization($organization, 'admin.organizations.diagnoses', $this->diagnosesOf($organization));
    }

    /**
     * The tabs of a main diagnosis and the page of any other diagnosis sit
     * under the diagnosis' list; its other pages (Adatok, Képességek,
     * Előnézet) under the diagnosis itself.
     */
    private function diagnosisPage(string $routeName, Diagnosis $diagnosis): AdminLocation
    {
        $isMain = $diagnosis->main()->exists();
        $isOwnPage = $routeName === 'admin.diagnoses.show' || ($isMain && in_array($routeName, self::MAIN_DIAGNOSIS_TABS, true));

        return $this->inOrganization(
            $diagnosis->organization,
            'admin.organizations.diagnoses',
            $isOwnPage ? $this->above($diagnosis) : [...$this->above($diagnosis), $this->diagnosisCrumb($diagnosis)],
            home: $isOwnPage && $isMain ? route('admin.diagnoses.show', $diagnosis) : null,
        );
    }

    /**
     * An uploaded material sits under what it was uploaded to; one of the
     * organization alone under the Folyamatok tab, which lists it.
     */
    private function materialPage(?Material $material): AdminLocation
    {
        $process = $material?->occasion?->process;

        return match (true) {
            $material?->parentDiagnosis !== null => $this->inOrganization(
                $material->parentDiagnosis->organization,
                'admin.organizations.diagnoses',
                [...$this->above($material->parentDiagnosis), $this->diagnosisCrumb($material->parentDiagnosis)],
            ),
            $process !== null => $this->inOrganization(
                $process->organization,
                'admin.organizations.processes',
                [
                    ...$this->tabCrumbs($process->organization, 'admin.organizations.processes'),
                    ['label' => $process->name, 'url' => route('admin.organizations.process-occasions', [$process->organization, $process])],
                ],
            ),
            $material?->organization !== null => $this->inOrganization(
                $material->organization,
                'admin.organizations.processes',
                $this->tabCrumbs($material->organization, 'admin.organizations.processes'),
            ),
            // Nothing to hang under: the Beérkező it arrived to.
            default => new AdminLocation(
                trail: [['label' => __('admin.nav.inbox'), 'url' => route('admin.inbox.index')]],
                section: 'admin.inbox.index',
                area: __('admin.nav.inbox'),
            ),
        };
    }

    /**
     * A page under its menu item: under the item's list, or nothing above the list itself.
     *
     * @param  array<string, mixed>  $parameters
     */
    private function listPage(string $routeName, array $parameters = []): AdminLocation
    {
        $item = $this->navigation->itemFor($routeName, auth('admin')->user());

        if ($item === null) {
            return new AdminLocation;
        }

        [$parameter, $homeRoute] = self::LIBRARY_HOMES[$routeName] ?? [null, null];

        return new AdminLocation(
            trail: $item['route'] === $routeName ? [] : [['label' => $item['label'], 'url' => route($item['route'])]],
            home: $homeRoute !== null && isset($parameters[$parameter]) ? route($homeRoute, $parameters[$parameter]) : null,
            section: $item['route'],
            area: $item['group'] ?? $item['label'],
        );
    }

    /**
     * A page of an organization: its name and tabs head the page, the tab
     * lit. Its trail starts with the organization's crumbs (links()).
     *
     * @param  list<array{label: string, url: string}>  $trail
     */
    private function inOrganization(Organization $organization, string $tab, array $trail, ?string $home = null): AdminLocation
    {
        return new AdminLocation(
            trail: $trail,
            organization: $organization,
            tab: $tab,
            home: $home,
            section: 'admin.organizations.index',
        );
    }

    /**
     * The pages above a diagnosis' own page.
     *
     * @return list<array{label: string, url: string}>
     */
    private function above(Diagnosis $diagnosis): array
    {
        $parent = $diagnosis->parent;

        if ($parent === null) {
            return $this->diagnosesOf($diagnosis->organization);
        }

        $trail = [...$this->above($parent), $this->diagnosisCrumb($parent)];

        if ($parent->main()->exists()) {
            $trail[] = ['label' => __('admin.main_diagnoses.tabs.subdiagnoses'), 'url' => route('admin.main-diagnoses.subdiagnoses', $parent)];
        }

        return $trail;
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    private function diagnosesOf(Organization $organization): array
    {
        return $this->tabCrumbs($organization, 'admin.organizations.diagnoses');
    }

    /**
     * The crumbs down to a tab of the organization (members, processes, diagnoses).
     *
     * @return list<array{label: string, url: string}>
     */
    private function tabCrumbs(Organization $organization, string $tab): array
    {
        return [
            ...$this->organizationCrumbs($organization),
            ['label' => __('admin.organizations.tabs.'.Str::afterLast($tab, '.')), 'url' => route($tab, $organization)],
        ];
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    private function organizationCrumbs(Organization $organization): array
    {
        return [
            ['label' => __('admin.nav.organizations'), 'url' => route('admin.organizations.index')],
            ['label' => $organization->name, 'url' => route('admin.organizations.edit', $organization)],
        ];
    }

    /**
     * @return array{label: string, url: string}
     */
    private function diagnosisCrumb(Diagnosis $diagnosis): array
    {
        return ['label' => $diagnosis->name, 'url' => route('admin.diagnoses.show', $diagnosis)];
    }

    /**
     * The route parameter as a model: bound already on a page request, a key otherwise.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $class
     * @return TModel|null
     */
    private function model(string $class, mixed $value): ?Model
    {
        if ($value === null || $value instanceof $class) {
            return $value;
        }

        return $class::query()->where((new $class)->getRouteKeyName(), $value)->first();
    }
}
