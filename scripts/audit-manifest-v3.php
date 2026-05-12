<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

$autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (is_file($autoload)) {
    require_once $autoload;
}

const CAPELL_MANIFEST_V3_PROVIDER_BUCKETS = [
    'metadata',
    'install',
    'runtime',
    'admin',
    'frontend',
];

const CAPELL_MANIFEST_V3_REQUIRED_ROOT_FIELDS = [
    'manifest-version',
    'name',
    'slug',
    'displayName',
    'kind',
    'capellApiVersion',
    'version',
    'description',
    'product',
    'namespace',
    'surfaces',
    'dependencies',
    'providers',
    'contributes',
    'database',
    'commands',
    'settings',
    'permissions',
    'capabilities',
    'performance',
    'healthChecks',
    'commercial',
    'marketplace',
];

const CAPELL_MANIFEST_V3_MIGRATION_GROUPS = [
    'foundation' => [
        'blog',
        'content-sections',
        'demo-kit',
        'foundation-theme',
        'frontend-authoring',
        'frontend-optimizer',
        'hero',
        'html-cache',
        'media-library',
        'navigation',
        'site-discovery',
        'tags',
        'welcome-tour',
    ],
    'operations' => [
        'access-gate',
        'dashboard-reports',
        'deployments',
        'diagnostics',
        'ga4-reports',
        'insights',
        'login-audit',
        'migration-assistant',
        'notes',
        'password-policy',
        'publishing-studio',
        'translation-manager',
    ],
    'content-product' => [
        'address',
        'agent-bridge',
        'ai-orchestrator',
        'campaign-studio',
        'email-studio',
        'events',
        'form-builder',
        'media-ai',
        'newsletter',
        'public-actions',
        'search',
        'seo-suite',
        'wordpress-importer',
    ],
    'themes' => [
        'theme-agency',
        'theme-corporate',
        'theme-saas',
    ],
];

const CAPELL_MANIFEST_V3_CONTRIBUTION_PATTERNS = [
    'admin-page' => 'registerExtensionPage',
    'dashboard-widget' => 'registerDashboardWidget',
    'overview-stat' => 'registerOverviewStat',
    'admin-resource' => 'AdminSurfaceContributionData::resource',
    'configurator' => 'AdminSurfaceContributionData::configurator',
    'schema-extender' => 'SchemaExtenderEnum::',
    'model' => 'CapellCore::registerModels',
    'section' => 'CapellCore::registerSection',
    'page-type' => 'CapellCore::registerPageType',
    'permission' => 'CapellCore::registerPermission',
    'route' => 'Route::',
    'setting' => 'CapellCore::registerSettings',
    'page-variation' => 'CapellCore::registerPageVariation',
    'asset' => 'CapellCore::registerAsset',
    'migration' => 'loadMigrationsFrom',
    'scheduled-job' => 'Schedule::',
    'agent-capability' => 'CapellAgentBridgeCapabilityRegistry',
];

/**
 * @return array{
 *     packages: array<string, array<string, mixed>>,
 *     errors: array<string, list<string>>,
 *     unassignedPackages: list<string>,
 *     duplicateAssignments: array<string, list<string>>
 * }
 */
function capell_manifest_v3_audit(string $root): array
{
    $packagesPath = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'packages';
    $assignments = capell_manifest_v3_package_assignments();
    $errors = [];
    $packages = [];

    foreach (capell_manifest_v3_package_directories($packagesPath) as $slug => $packagePath) {
        $manifestPath = $packagePath . DIRECTORY_SEPARATOR . 'capell.json';
        $manifest = is_file($manifestPath)
            ? json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR)
            : [];

        $discoveredTypes = capell_manifest_v3_discovered_contribution_types($packagePath);
        $declaredTypes = capell_manifest_v3_declared_contribution_types($manifest);
        $deferredTypes = capell_manifest_v3_deferred_contribution_types($manifest);
        $missingTypes = array_values(array_diff($discoveredTypes, [...$declaredTypes, ...$deferredTypes]));
        sort($missingTypes);

        $packageErrors = [
            ...capell_manifest_v3_missing_field_errors($manifest),
            ...capell_manifest_v3_provider_bucket_errors($manifest),
        ];

        if (! array_key_exists($slug, $assignments)) {
            $packageErrors[] = 'package is not assigned to a migration group';
        }

        foreach ($missingTypes as $type) {
            $packageErrors[] = "discovered {$type} contribution is not declared or deferred";
        }

        if ($packageErrors !== []) {
            $errors[$slug] = $packageErrors;
        }

        $packages[$slug] = [
            'name' => $manifest['name'] ?? null,
            'manifestVersion' => $manifest['manifest-version'] ?? null,
            'migrationGroup' => $assignments[$slug][0] ?? null,
            'manifestPath' => is_file($manifestPath) ? $manifestPath : null,
            'missingFields' => capell_manifest_v3_missing_fields($manifest),
            'discoveredContributionTypes' => $discoveredTypes,
            'declaredContributionTypes' => $declaredTypes,
            'deferredContributionTypes' => $deferredTypes,
            'missingContributionTypes' => $missingTypes,
        ];
    }

    ksort($packages);
    ksort($errors);

    return [
        'packages' => $packages,
        'errors' => $errors,
        'unassignedPackages' => capell_manifest_v3_unassigned_packages($packagesPath),
        'duplicateAssignments' => capell_manifest_v3_duplicate_assignments(),
    ];
}

/**
 * @return array<string, string>
 */
function capell_manifest_v3_product_groups(): array
{
    $groups = [];

    foreach (CAPELL_MANIFEST_V3_MIGRATION_GROUPS as $group => $packages) {
        foreach ($packages as $package) {
            $groups[$package] = $group;
        }
    }

    ksort($groups);

    return $groups;
}

/**
 * @return array<string, list<string>>
 */
function capell_manifest_v3_package_assignments(): array
{
    $assignments = [];

    foreach (CAPELL_MANIFEST_V3_MIGRATION_GROUPS as $group => $packages) {
        foreach ($packages as $package) {
            $assignments[$package][] = $group;
        }
    }

    ksort($assignments);

    return $assignments;
}

/**
 * @return array<string, string>
 */
function capell_manifest_v3_package_directories(string $packagesPath): array
{
    $directories = [];

    foreach (glob($packagesPath . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [] as $packagePath) {
        if (! is_file($packagePath . DIRECTORY_SEPARATOR . 'composer.json')) {
            continue;
        }

        $directories[basename($packagePath)] = $packagePath;
    }

    ksort($directories);

    return $directories;
}

/**
 * @return array<string, array<string, mixed>>
 */
function capell_manifest_v3_manifest_payloads(string $root): array
{
    $finder = (new Finder)
        ->in(rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'packages')
        ->name('capell.json')
        ->depth('< 4');

    $payloads = [];

    foreach ($finder as $manifest) {
        $payloads[$manifest->getRelativePathname()] = json_decode(
            $manifest->getContents(),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    ksort($payloads);

    return $payloads;
}

/**
 * @return list<string>
 */
function capell_manifest_v3_unassigned_packages(string $packagesPath): array
{
    $assignments = capell_manifest_v3_package_assignments();
    $unassigned = array_values(array_diff(array_keys(capell_manifest_v3_package_directories($packagesPath)), array_keys($assignments)));
    sort($unassigned);

    return $unassigned;
}

/**
 * @return array<string, list<string>>
 */
function capell_manifest_v3_duplicate_assignments(): array
{
    return array_filter(
        capell_manifest_v3_package_assignments(),
        static fn (array $groups): bool => count($groups) !== 1,
    );
}

/**
 * @param  array<string, mixed>  $manifest
 * @return list<string>
 */
function capell_manifest_v3_missing_fields(array $manifest): array
{
    return array_values(array_filter(
        CAPELL_MANIFEST_V3_REQUIRED_ROOT_FIELDS,
        static fn (string $field): bool => ! array_key_exists($field, $manifest),
    ));
}

/**
 * @param  array<string, mixed>  $manifest
 * @return list<string>
 */
function capell_manifest_v3_missing_field_errors(array $manifest): array
{
    return array_map(
        static fn (string $field): string => "missing {$field}",
        capell_manifest_v3_missing_fields($manifest),
    );
}

/**
 * @param  array<string, mixed>  $manifest
 * @return list<string>
 */
function capell_manifest_v3_provider_bucket_errors(array $manifest): array
{
    if (! is_array($manifest['providers'] ?? null)) {
        return ['missing providers'];
    }

    $actual = array_keys($manifest['providers']);
    sort($actual);
    $expected = CAPELL_MANIFEST_V3_PROVIDER_BUCKETS;
    sort($expected);

    return $actual === $expected
        ? []
        : ['providers must declare metadata, install, runtime, admin, and frontend buckets'];
}

/**
 * @return list<string>
 */
function capell_manifest_v3_discovered_contribution_types(string $packagePath): array
{
    $types = [];
    $finder = (new Finder)
        ->files()
        ->in($packagePath)
        ->name('*.php')
        ->exclude(['vendor']);

    foreach ($finder as $file) {
        $contents = $file->getContents();

        foreach (CAPELL_MANIFEST_V3_CONTRIBUTION_PATTERNS as $type => $pattern) {
            if (str_contains($contents, $pattern)) {
                $types[$type] = true;
            }
        }
    }

    $types = array_keys($types);
    sort($types);

    return $types;
}

/**
 * @param  array<string, mixed>  $manifest
 * @return list<string>
 */
function capell_manifest_v3_declared_contribution_types(array $manifest): array
{
    if (! is_array($manifest['contributes'] ?? null)) {
        return [];
    }

    $types = [];

    foreach ($manifest['contributes'] as $contribution) {
        if (is_array($contribution) && is_string($contribution['type'] ?? null)) {
            $types[$contribution['type']] = true;
        }
    }

    $types = array_keys($types);
    sort($types);

    return $types;
}

/**
 * @param  array<string, mixed>  $manifest
 * @return list<string>
 */
function capell_manifest_v3_deferred_contribution_types(array $manifest): array
{
    $runtime = $manifest['contributionTraceability'] ?? $manifest['runtime'] ?? [];

    if (! is_array($runtime) || ! is_array($runtime['deferredContributions'] ?? null)) {
        return [];
    }

    $types = array_values(array_filter(
        $runtime['deferredContributions'],
        static fn (mixed $type): bool => is_string($type) && $type !== '',
    ));
    sort($types);

    return $types;
}

if (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    $report = capell_manifest_v3_audit(dirname(__DIR__));

    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

    return $report['errors'] === [] && $report['unassignedPackages'] === [] && $report['duplicateAssignments'] === [] ? 0 : 1;
}
