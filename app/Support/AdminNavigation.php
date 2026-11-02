<?php

namespace App\Support;

use App\Models\User;
use App\Services\Inbox\InboxService;
use Closure;
use Illuminate\Support\Str;

/**
 * The left menu of the admin (01 · 1.5): its groups and items. The menu draws
 * from it, and the breadcrumbs (AdminBreadcrumbs) find the list a page
 * belongs to by the items' active patterns.
 *
 * @phpstan-type Item array{route: string, active: string|list<string>, label: string, count?: Closure(): int}
 * @phpstan-type Group array{label: ?string, items: list<Item>}
 */
class AdminNavigation
{
    public function __construct(private readonly InboxService $inbox) {}

    /**
     * The counts are closures, so only the menu runs their queries.
     *
     * @return list<Group>
     */
    public function groups(?User $admin): array
    {
        $groups = [
            [
                'label' => null,
                'items' => [
                    ['route' => 'admin.inbox.index', 'active' => 'admin.inbox.*', 'label' => __('admin.nav.inbox'), 'count' => fn (): int => $this->inbox->openCount($admin)],
                ],
            ],
            [
                'label' => __('admin.nav.organizations'),
                'items' => [
                    ['route' => 'admin.organizations.index', 'active' => 'admin.organizations.*', 'label' => __('admin.nav.organizations')],
                    ['route' => 'admin.clients.index', 'active' => 'admin.clients.*', 'label' => __('admin.nav.clients')],
                ],
            ],
            [
                'label' => __('admin.nav.diagnoses'),
                'items' => [
                    // The pages of a diagnosis belong to its organization (AdminBreadcrumbs).
                    ['route' => 'admin.diagnoses.index', 'active' => ['admin.diagnoses.index', 'admin.diagnoses.create', 'admin.diagnoses.upload', 'admin.main-diagnoses.create'], 'label' => __('admin.nav.all_diagnoses')],
                    ['route' => 'admin.diagnosis-types.index', 'active' => 'admin.diagnosis-types.*', 'label' => __('admin.nav.diagnosis_types')],
                    ['route' => 'admin.support-questions.index', 'active' => 'admin.support-questions.*', 'label' => __('admin.nav.support_questions')],
                ],
            ],
            [
                'label' => __('admin.nav.measurements'),
                'items' => [
                    ['route' => 'admin.mirrors.index', 'active' => 'admin.mirrors.*', 'label' => __('admin.nav.mirrors')],
                    ['route' => 'admin.measurements.index', 'active' => 'admin.measurements.*', 'label' => __('admin.nav.inbox'), 'count' => fn (): int => $this->inbox->openMeasurementCount($admin)],
                ],
            ],
            [
                'label' => __('admin.nav.value_library'),
                'items' => [
                    ['route' => 'admin.values.index', 'active' => ['admin.values.index', 'admin.values.create', 'admin.values.edit', 'admin.values.versions'], 'label' => __('admin.nav.values')],
                    ['route' => 'admin.value-categories.index', 'active' => 'admin.value-categories.*', 'label' => __('admin.nav.value_categories')],
                    ['route' => 'admin.values.import', 'active' => 'admin.values.import', 'label' => __('admin.nav.value_import')],
                ],
            ],
            [
                'label' => __('admin.nav.capability_library'),
                'items' => [
                    ['route' => 'admin.capabilities.index', 'active' => ['admin.capabilities.index', 'admin.capabilities.create', 'admin.capabilities.edit', 'admin.capabilities.patterns', 'admin.capabilities.versions'], 'label' => __('admin.nav.capabilities')],
                    ['route' => 'admin.patterns.index', 'active' => 'admin.patterns.*', 'label' => __('admin.nav.patterns')],
                    ['route' => 'admin.capability-groups.index', 'active' => 'admin.capability-groups.*', 'label' => __('admin.nav.capability_groups')],
                    ['route' => 'admin.capability-rules.index', 'active' => 'admin.capability-rules.*', 'label' => __('admin.nav.capability_rules')],
                    ['route' => 'admin.capabilities.import', 'active' => 'admin.capabilities.import', 'label' => __('admin.nav.capability_import')],
                ],
            ],
            [
                'label' => __('admin.nav.settings'),
                'items' => [
                    ['route' => 'admin.products.index', 'active' => 'admin.products.*', 'label' => __('admin.nav.products')],
                    ['route' => 'admin.admins.index', 'active' => 'admin.admins.*', 'label' => __('admin.nav.admins')],
                    ['route' => 'admin.settings.legal', 'active' => ['admin.settings.legal', 'admin.legal.version'], 'label' => __('admin.nav.legal_documents')],
                    ['route' => 'admin.settings.areas', 'active' => 'admin.settings.areas', 'label' => __('admin.nav.areas')],
                ],
            ],
        ];

        if ($admin?->can('viewSystem')) {
            $groups[] = [
                'label' => __('admin.nav.system'),
                'items' => [
                    ['route' => 'admin.system.index', 'active' => 'admin.system.index', 'label' => __('admin.nav.system_overview')],
                    ['route' => 'admin.system.health', 'active' => 'admin.system.health', 'label' => __('admin.nav.system_health')],
                    ['route' => 'admin.system.ai.usage', 'active' => 'admin.system.ai.usage', 'label' => __('admin.nav.ai_usage')],
                    ['route' => 'admin.system.ai.configuration', 'active' => 'admin.system.ai.configuration', 'label' => __('admin.nav.ai_configuration')],
                    ['route' => 'admin.system.ai.prompts', 'active' => 'admin.system.ai.prompts', 'label' => __('admin.nav.ai_prompts')],
                ],
            ];
        }

        return $groups;
    }

    /**
     * The menu item whose active patterns cover the route, the first one, with
     * its group's label.
     *
     * @return array{route: string, active: string|list<string>, label: string, group: ?string}|null
     */
    public function itemFor(string $routeName, ?User $admin): ?array
    {
        foreach ($this->groups($admin) as $group) {
            foreach ($group['items'] as $item) {
                if (Str::is($item['active'], $routeName)) {
                    return $item + ['group' => $group['label']];
                }
            }
        }

        return null;
    }
}
