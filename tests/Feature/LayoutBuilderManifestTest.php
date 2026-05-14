<?php

declare(strict_types=1);

use Illuminate\Support\Arr;

it('declares the admin resources and extension points owned by layout builder', function (): void {
    $manifest = json_decode(
        file_get_contents(dirname(__DIR__, 2) . '/capell.json') ?: '[]',
        true,
    );

    $contributionTypes = collect($manifest['contributes'] ?? [])->pluck('type')->all();
    $deferredTypes = $manifest['contributionTraceability']['deferredContributions'] ?? [];

    expect($contributionTypes)->toContain('admin-resource', 'configurator', 'schema-extender', 'asset')
        ->and($deferredTypes)->not->toContain('permission', 'configurator', 'schema-extender', 'asset');
});

it('advertises package-owned layout builder admin classes in its manifest', function (): void {
    $manifest = json_decode(
        file_get_contents(dirname(__DIR__, 2) . '/capell.json') ?: '[]',
        true,
    );

    $manifestStrings = collect($manifest['contributes'] ?? [])
        ->flatMap(fn (array $contribution): array => Arr::flatten($contribution))
        ->filter(fn (mixed $value): bool => is_string($value));

    expect($manifestStrings->filter(fn (string $value): bool => str_starts_with($value, 'Capell\\Admin\\LayoutBuilder\\')))->toBeEmpty()
        ->and($manifestStrings)->toContain(
            'Capell\\LayoutBuilder\\Filament\\Resources\\Layouts\\LayoutResource',
            'Capell\\LayoutBuilder\\Filament\\Resources\\Widgets\\WidgetResource',
            'Capell\\LayoutBuilder\\Enums\\ConfiguratorTypeEnum',
        );
});
