<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Filament\Resources\Layouts\LayoutResource;
use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
use Illuminate\Support\Arr;

it('declares the admin resources and extension points owned by layout builder', function (): void {
    $manifestContents = file_get_contents(dirname(__DIR__, 2) . '/capell.json');
    $manifest = json_decode(
        $manifestContents !== false ? $manifestContents : '[]',
        true,
    );

    $contributionTypes = capell_test_collect($manifest['contributes'] ?? [])->pluck('type')->all();
    $deferredTypes = $manifest['contributionTraceability']['deferredContributions'] ?? [];

    expect($contributionTypes)->toContain('admin-resource', 'configurator', 'schema-extender', 'asset')
        ->and($deferredTypes)->not->toContain('permission', 'configurator', 'schema-extender', 'asset');
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
});

it('declares all package-owned storage tables in the manifest', function (): void {
    $manifest = layoutBuilderJson('capell.json');

    expect($manifest['database']['requiredTables'] ?? [])->toBe([
        'layouts',
        'widgets',
        'widget_assets',
        'widget_widgets',
        'layout_presets',
        'layout_bulk_change_runs',
        'layout_bulk_change_results',
    ]);
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
