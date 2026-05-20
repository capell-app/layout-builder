<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Filament\Resources\Blocks\BlockResource;
use Capell\LayoutBuilder\Filament\Resources\Layouts\LayoutResource;
use Illuminate\Support\Arr;

it('declares the admin resources and extension points owned by layout builder', function (): void {
    $manifestContents = file_get_contents(dirname(__DIR__, 2) . '/capell.json');
    $manifest = json_decode(
        $manifestContents !== false ? $manifestContents : '[]',
        true,
    );

    $contributionTypes = collect($manifest['contributes'] ?? [])->pluck('type')->all();
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

    $manifestStrings = collect($manifest['contributes'] ?? [])
        ->flatMap(fn (array $contribution): array => Arr::flatten($contribution))
        ->filter(fn (mixed $value): bool => is_string($value));

    expect($manifestStrings->filter(fn (string $value): bool => str_starts_with($value, 'Capell\\Admin\\LayoutBuilder\\')))->toBeEmpty()
        ->and($manifestStrings)->toContain(
            LayoutResource::class,
            BlockResource::class,
            ConfiguratorTypeEnum::class,
        );
});
