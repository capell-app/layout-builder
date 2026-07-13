<?php

declare(strict_types=1);

use Capell\Core\Contracts\Extensions\RegistersExtensionPageType;
use Capell\Core\Contracts\Extensions\RegistersExtensionRoute;
use Capell\Core\Contracts\Extensions\RunsExtensionMigration;
use Capell\Core\Contracts\PackageLifecycleAction;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Filament\Resources\Layouts\LayoutResource;
use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
use Capell\LayoutBuilder\Manifest\LayoutBuilderMigrationsContribution;
use Capell\LayoutBuilder\Manifest\LayoutBuilderModelsContribution;
use Capell\LayoutBuilder\Manifest\LayoutBuilderPageTypesContribution;
use Capell\LayoutBuilder\Manifest\LayoutBuilderRoutesContribution;
use Capell\LayoutBuilder\Models\LayoutBulkChangeResult;
use Capell\LayoutBuilder\Models\LayoutBulkChangeRun;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Capell\LayoutBuilder\Models\LayoutPresetSyncResult;
use Capell\LayoutBuilder\Models\LayoutPresetSyncRun;
use Capell\LayoutBuilder\Models\LayoutPresetUsage;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Models\WidgetWidget;
use Illuminate\Support\Arr;

it('declares the admin resources and extension points owned by layout builder', function (): void {
    $manifestContents = file_get_contents(dirname(__DIR__, 2) . '/capell.json');
    $manifest = json_decode(
        $manifestContents !== false ? $manifestContents : '[]',
        true,
    );

    $contributionTypes = capell_test_collect($manifest['contributes'] ?? [])->pluck('type')->all();
    $deferredTypes = $manifest['contributionTraceability']['deferredContributions'] ?? [];

    expect($contributionTypes)->toContain(
        'admin-resource',
        'asset',
        'configurator',
        'migration',
        'model',
        'page-type',
        'route',
        'schema-extender',
    )
        ->and($deferredTypes)->toBe([]);
});

it('advertises package-owned layout builder admin classes in its manifest', function (): void {
    $manifestContents = file_get_contents(dirname(__DIR__, 2) . '/capell.json');
    $manifest = json_decode(
        $manifestContents !== false ? $manifestContents : '[]',
        true,
    );

    $manifestStrings = capell_test_collect($manifest['contributes'] ?? [])
        ->flatMap(fn (array $contribution): array => Arr::flatten($contribution))
        ->filter(fn (mixed $value): bool => is_string($value));

    expect($manifestStrings->filter(fn (string $value): bool => str_starts_with($value, 'Capell\\Admin\\LayoutBuilder\\')))->toBeEmpty()
        ->and($manifestStrings)->toContain(
            LayoutResource::class,
            WidgetResource::class,
            ConfiguratorTypeEnum::class,
        );
});

it('keeps manifest hard dependencies aligned with composer requirements', function (): void {
    $manifest = layoutBuilderJson('capell.json');
    $composer = layoutBuilderJson('composer.json');

    $manifestRequires = $manifest['dependencies']['requires'] ?? [];
    $composerRequires = array_keys($composer['require'] ?? []);

    expect($manifestRequires)->toContain(
        'capell-app/admin',
        'capell-app/block-library',
        'capell-app/core',
        'capell-app/frontend',
    );

    foreach ($manifestRequires as $requiredPackage) {
        expect($composerRequires)->toContain($requiredPackage);
    }

    expect($composer['require']['capell-app/admin'] ?? null)->toBe('^4.0')
        ->and($composer['require']['capell-app/block-library'] ?? null)->toBe('^4.0')
        ->and($composer['require']['capell-app/core'] ?? null)->toBe('^4.0')
        ->and($composer['require']['capell-app/frontend'] ?? null)->toBe('^4.0')
        ->and($manifest['capellApiVersion'] ?? null)->toBe('^4.0');
});

it('declares all package-owned storage tables in the manifest', function (): void {
    $manifest = layoutBuilderJson('capell.json');

    expect(data_get($manifest, 'database.requiredTables', []))->toBe([
        'layouts',
        'widgets',
        'widget_assets',
        'widget_widgets',
        'layout_presets',
        'layout_bulk_change_runs',
        'layout_bulk_change_results',
        'layout_preset_usages',
        'layout_preset_sync_runs',
        'layout_preset_sync_results',
        'public_widget_snapshots',
    ]);
});

it('declares runtime model page type route and migration contribution metadata', function (): void {
    $manifest = layoutBuilderJson('capell.json');
    $contributes = $manifest['contributes'] ?? [];
    $security = $manifest['security'] ?? null;

    throw_unless(is_array($contributes), RuntimeException::class, 'Expected Layout Builder contributions array.');
    throw_unless(is_array($security), RuntimeException::class, 'Expected Layout Builder security metadata array.');
    throw_unless(is_array($security['publicSurface'] ?? null), RuntimeException::class, 'Expected Layout Builder public surface metadata array.');

    $contributions = collect($contributes);

    $models = $contributions->firstWhere('class', LayoutBuilderModelsContribution::class);
    $pageTypes = $contributions->firstWhere('class', LayoutBuilderPageTypesContribution::class);
    $routes = $contributions->firstWhere('class', LayoutBuilderRoutesContribution::class);
    $migrations = $contributions->firstWhere('class', LayoutBuilderMigrationsContribution::class);

    throw_unless(is_array($models), RuntimeException::class, 'Expected Layout Builder model contribution array.');
    throw_unless(is_array($pageTypes), RuntimeException::class, 'Expected Layout Builder page type contribution array.');
    throw_unless(is_array($routes), RuntimeException::class, 'Expected Layout Builder route contribution array.');
    throw_unless(is_array($migrations), RuntimeException::class, 'Expected Layout Builder migration contribution array.');

    expect($models)->toBeArray()
        ->and($models['modelClasses'])->toBe([
            Widget::class,
            WidgetAsset::class,
            WidgetWidget::class,
            LayoutPreset::class,
            LayoutPresetUsage::class,
            LayoutPresetSyncRun::class,
            LayoutPresetSyncResult::class,
            LayoutBulkChangeRun::class,
            LayoutBulkChangeResult::class,
        ])
        ->and($models['morphAliases'])->toBe([
            'widget' => Widget::class,
            'widget_asset' => WidgetAsset::class,
            'widget_widget' => WidgetWidget::class,
        ])
        ->and($pageTypes['pageTypes'])->toBe([
            [
                'name' => 'widget',
                'modelClass' => Widget::class,
            ],
        ])
        ->and(class_implements(LayoutBuilderPageTypesContribution::class))->toContain(RegistersExtensionPageType::class)
        ->and($routes['routes'])->toBe([
            'capell-layout-builder.fragments.show',
            'capell-layout-builder.layout-widgets.show',
        ])
        ->and($routes['reservedFrontendPath'])->toBe('_fragments')
        ->and($security['publicSurface']['routeNames'])->toBe([
            'capell-layout-builder.fragments.show',
            'capell-layout-builder.layout-widgets.show',
        ])
        ->and(class_implements(LayoutBuilderRoutesContribution::class))->toContain(RegistersExtensionRoute::class)
        ->and($migrations['migrationFiles'])->toBe([
            '2026_05_10_190841_01_create_layouts_table',
            '2026_05_10_190841_02_create_widgets_table',
            '2026_05_10_190841_03_create_widget_assets_table',
            '2026_05_10_190841_04_create_widget_widgets_table',
            '2026_05_10_190841_05_add_container_widgets_to_layouts_table',
            '2026_05_10_190841_06_create_layout_presets_table',
            '2026_06_07_000001_create_layout_bulk_change_tables',
            '2026_07_09_000001_create_public_widget_snapshots_table',
            '2026_07_10_000001_add_linked_preset_fields_to_layout_presets_table',
            '2026_07_10_000002_create_layout_preset_usages_table',
            '2026_07_10_000003_create_layout_preset_sync_runs_table',
        ])
        ->and(class_implements(LayoutBuilderMigrationsContribution::class))->toContain(RunsExtensionMigration::class);
});

it('declares lifecycle actions that satisfy the installer contract', function (): void {
    $manifest = layoutBuilderJson('capell.json');
    $actions = $manifest['actions'] ?? [];
    throw_unless(is_array($actions), RuntimeException::class, 'Layout Builder actions must be an array.');

    expect($actions)->toHaveKeys(['install', 'setup']);

    foreach (['install', 'setup'] as $lifecycle) {
        $actionClass = $actions[$lifecycle] ?? null;
        throw_unless(is_string($actionClass), RuntimeException::class, 'Lifecycle action class must be a string.');

        expect($actionClass)->toBeString()
            ->and(class_exists($actionClass))->toBeTrue()
            ->and(is_subclass_of($actionClass, PackageLifecycleAction::class))->toBeTrue();
    }
});

it('keeps public layout output cacheable with invalidation metadata', function (): void {
    // Layout Builder renders query-free public HTML, so its frontend output is
    // origin-cacheable. The cacheable flag must agree with the public-output
    // contract and ship invalidation sources, or the manifest validator rejects
    // it ("cacheable frontend surfaces need invalidation metadata") and the
    // origin HTML cache silently stops populating for every public page.
    $manifest = layoutBuilderJson('capell.json');
    $cacheSafety = data_get($manifest, 'performance.cacheSafety');

    throw_unless(is_array($cacheSafety), RuntimeException::class, 'Expected Layout Builder cacheSafety metadata array.');

    expect($cacheSafety['cacheable'])->toBeTrue();
    expect($cacheSafety['sensitiveOutput'])->toBeFalse();
    expect(data_get($manifest, 'security.publicOutput.cacheSafe'))->toBeTrue();

    $invalidationSources = $cacheSafety['invalidationSources'] ?? [];
    throw_unless(is_array($invalidationSources), RuntimeException::class, 'Expected Layout Builder invalidation sources array.');

    $invalidationModels = collect($invalidationSources)->pluck('model')->all();

    expect($cacheSafety['invalidationSources'])->not->toBeEmpty();
    expect($invalidationModels)->toContain(
        Page::class,
        PageUrl::class,
        Widget::class,
        WidgetAsset::class,
        WidgetWidget::class,
        LayoutPreset::class,
    );
});

it('references committed marketplace and screenshot manifest images', function (): void {
    $manifest = layoutBuilderJson('capell.json');
    $screenshots = layoutBuilderJson('docs/screenshots.json');
    $packageRoot = dirname(__DIR__, 2);
    $repositoryRoot = dirname(__DIR__, 4);

    foreach ($manifest['marketplace']['screenshots'] ?? [] as $screenshot) {
        expect($screenshot)->toHaveKey('path')
            ->and(is_file($packageRoot . '/' . $screenshot['path']))->toBeTrue();
    }

    foreach ($screenshots['entries'] ?? [] as $entry) {
        expect($entry)->toHaveKey('screenshotPath')
            ->and(is_file($repositoryRoot . '/' . $entry['screenshotPath']))->toBeTrue();
    }
});

/**
 * @return array<string, mixed>
 */
function layoutBuilderJson(string $path): array
{
    $contents = file_get_contents(dirname(__DIR__, 2) . '/' . $path);

    return json_decode($contents !== false ? $contents : '[]', true, flags: JSON_THROW_ON_ERROR);
}
