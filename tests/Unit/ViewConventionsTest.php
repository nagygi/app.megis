<?php

/*
 * Keeps the views on the design system (UI finomhangolás, 1.18): no shadows,
 * no radius or weight outside the brand tokens, icons instead of Unicode signs,
 * and cards through x-ui.card. Each rule lists the views that still break it.
 * A list may only shrink: a new view breaking a rule fails, and so does a
 * listed view that no longer breaks it (take it off the list).
 */

/**
 * The views a rule applies to, keyed by their path under resources/views.
 * Mails and PDFs have their own stylesheets.
 *
 * @return array<string, string>
 */
function conventionViews(): array
{
    $root = dirname(__DIR__, 2).'/resources/views/';
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    $views = [];

    foreach ($files as $file) {
        $path = str_replace('\\', '/', substr($file->getPathname(), strlen($root)));

        if (! str_ends_with($path, '.blade.php') || preg_match('#^(vendor/mail|mail|pdf)/|^components/pdf-layout#', $path)) {
            continue;
        }

        // Comments may name a class to explain why it is not used.
        $views[$path] = preg_replace(['/\{\{--.*?--\}\}/s', '/<!--.*?-->/s'], '', file_get_contents($file->getPathname()));
    }

    ksort($views);

    return $views;
}

/**
 * @param  callable(string, string): bool  $breaks  receives the source and the path
 * @return list<string>
 */
function viewsBreaking(callable $breaks): array
{
    return array_keys(array_filter(conventionViews(), $breaks, ARRAY_FILTER_USE_BOTH));
}

/**
 * A class attribute holding a white card: rounded-card and bg-white together.
 */
function hasInlineCard(string $source): bool
{
    preg_match_all('/class="([^"]*)"/', $source, $classes);

    foreach ($classes[1] as $class) {
        if (preg_match('/(?<![\w-])rounded-card(?![\w-])/', $class) && preg_match('/(?<![\w:-])bg-white(?![\w-])/', $class)) {
            return true;
        }
    }

    return false;
}

it('keeps shadows out of the views: the identity is flat', function () {
    expect(viewsBreaking(fn (string $source) => (bool) preg_match('/(?<![\w-])(?:[a-z-]+:)*shadow-(?:2xs|xs|sm|md|lg|xl|2xl)(?![\w-])/', $source)))
        ->toBe([]);
});

it('uses only the brand radii: rounded-card, rounded-field, rounded-pill, rounded-full', function () {
    expect(viewsBreaking(fn (string $source) => (bool) preg_match('/(?<![\w-])(?:[a-z-]+:)*rounded(?:-(?:t|r|b|l|tl|tr|br|bl|s|e|ss|se|es|ee))?(?:-(?:xs|sm|md|lg|xl|2xl|3xl|4xl))?(?![\w-])/', $source)))
        ->toBe([]);
});

it('uses only the brand weights: 400, 500, 600 and 800', function () {
    expect(viewsBreaking(fn (string $source) => (bool) preg_match('/(?<![\w-])(?:[a-z-]+:)*font-(?:thin|extralight|light|bold|black)(?![\w-])/', $source)))
        ->toBe([]);
});

it('draws icons with x-megis.icon, not with Unicode signs', function () {
    expect(viewsBreaking(fn (string $source) => (bool) preg_match('/[‹›←→↑↓↕×▲▼⋮✓✔✕✖]/u', $source)))
        ->toEqual([
            'livewire/admin/clients/show.blade.php',
            'livewire/admin/diagnoses/show.blade.php',
            'livewire/admin/main-diagnoses/reading.blade.php',
            'livewire/admin/organizations/process-occasions.blade.php',
            'livewire/admin/organizations/values.blade.php',
            'livewire/admin/partials/version-history.blade.php',
            'livewire/admin/support-questions/index.blade.php',
        ]);
});

it('builds white cards with x-ui.card', function () {
    // The design system's own surfaces (card, dialog, menus) live in components/ui.
    expect(viewsBreaking(fn (string $source, string $path) => ! str_starts_with($path, 'components/ui/') && hasInlineCard($source)))
        ->toEqual([
            'livewire/admin/admin-users/form.blade.php',
            'livewire/admin/capabilities/form.blade.php',
            'livewire/admin/capabilities/import.blade.php',
            'livewire/admin/capability-groups/form.blade.php',
            'livewire/admin/clients/show.blade.php',
            'livewire/admin/diagnoses/form.blade.php',
            'livewire/admin/diagnoses/index.blade.php',
            'livewire/admin/diagnoses/show.blade.php',
            'livewire/admin/diagnoses/upload.blade.php',
            'livewire/admin/diagnosis-types/form.blade.php',
            'livewire/admin/inbox/index.blade.php',
            'livewire/admin/main-diagnoses/form.blade.php',
            'livewire/admin/main-diagnoses/publish.blade.php',
            'livewire/admin/main-diagnoses/reading.blade.php',
            'livewire/admin/main-diagnoses/run.blade.php',
            'livewire/admin/materials/partials/source.blade.php',
            'livewire/admin/materials/show.blade.php',
            'livewire/admin/organizations/form.blade.php',
            'livewire/admin/organizations/member-form.blade.php',
            'livewire/admin/organizations/process-occasions.blade.php',
            'livewire/admin/organizations/processes.blade.php',
            'livewire/admin/organizations/teams.blade.php',
            'livewire/admin/organizations/values.blade.php',
            'livewire/admin/partials/version-history.blade.php',
            'livewire/admin/products/form.blade.php',
            'livewire/admin/profile.blade.php',
            'livewire/admin/settings/areas.blade.php',
            'livewire/admin/settings/legal-documents.blade.php',
            'livewire/admin/support-questions/index.blade.php',
            'livewire/admin/system/ai-prompts.blade.php',
            'livewire/admin/system/ai-usage.blade.php',
            'livewire/admin/system/health.blade.php',
            'livewire/admin/system/overview.blade.php',
            'livewire/admin/value-categories/form.blade.php',
            'livewire/admin/values/form.blade.php',
        ]);
});
