<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('does not use retired core layout builder namespaces', function (): void {
    $sourcePath = dirname(__DIR__, 2) . '/src';

    $legacyReferences = collect((new Finder)->files()->in($sourcePath)->name('*.php'))
        ->mapWithKeys(function (SplFileInfo $file): array {
            $filePath = $file->getRealPath() !== false ? $file->getRealPath() : $file->getPathname();
            $relativePath = str_replace(dirname(__DIR__, 2) . '/', '', $filePath);
            $contents = file_get_contents($filePath);

            if (! is_string($contents)) {
                throw new RuntimeException(sprintf('Expected %s to be readable.', $relativePath));
            }

            return [$relativePath => $contents];
        })
        ->filter(fn (string $contents): bool => str_contains($contents, 'Capell\\Core\\LayoutBuilder\\'))
        ->keys()
        ->sort()
        ->values()
        ->all();

    expect($legacyReferences)->toBe([]);
});

it('keeps companion package source off legacy layout builder namespaces', function (): void {
    $packagesPath = dirname(__DIR__, 3);

    $legacyReferences = collect((new Finder)->files()->in($packagesPath)->exclude('layout-builder')->name(['*.php', '*.blade.php']))
        ->filter(fn (SplFileInfo $file): bool => str_contains($file->getPathname(), '/src/')
            || str_contains($file->getPathname(), '/resources/'))
        ->mapWithKeys(function (SplFileInfo $file) use ($packagesPath): array {
            $filePath = $file->getRealPath() !== false ? $file->getRealPath() : $file->getPathname();
            $relativePath = str_replace($packagesPath . '/', '', $filePath);
            $contents = file_get_contents($filePath);

            if (! is_string($contents)) {
                throw new RuntimeException(sprintf('Expected %s to be readable.', $relativePath));
            }

            return [$relativePath => $contents];
        })
        ->filter(fn (string $contents): bool => str_contains($contents, 'Capell\\Core\\LayoutBuilder\\')
            || str_contains($contents, 'Capell\\Admin\\LayoutBuilder\\'))
        ->keys()
        ->sort()
        ->values()
        ->all();

    expect($legacyReferences)->toBe([]);
});

it('keeps optional package integrations behind explicit gates', function (): void {
    $sourcePath = dirname(__DIR__, 2) . '/src';
    $allowedIntegrationPaths = [
        'src/Filament/Configurators/Blocks/RegisteredAssetWidgetAssetForm.php',
        'src/LayoutBuilderServiceProvider.php',
        'src/Livewire/Filament/Support/LayoutBuilderActionFactory.php',
        'src/Support/Creator/TypeCreator.php',
        'src/Support/FrontendAuthoring/',
    ];
    $optionalImports = [
        'use Capell\\ContentSections\\',
        'use Capell\\FrontendAuthoring\\',
        'use Capell\\HtmlCache\\',
        'use Capell\\PublishingStudio\\',
    ];
    $optionalClassConstants = [
        'CreateRecordDraftWorkspaceAction::class',
        'SaveRecordDraftAction::class',
        'Workspace::class',
        'WorkspaceContext::class',
        'WorkspaceRegistry::class',
        'ClearCachedUrlsForModelAction::class',
        'Section::class',
        'EditableRegionEditorSurface::class',
        'EditorSurfaceRegistry::class',
    ];

    $violations = collect((new Finder)->files()->in($sourcePath)->name('*.php'))
        ->mapWithKeys(function (SplFileInfo $file): array {
            $filePath = $file->getRealPath() !== false ? $file->getRealPath() : $file->getPathname();
            $relativePath = str_replace(dirname(__DIR__, 2) . '/', '', $filePath);
            $contents = file_get_contents($filePath);

            if (! is_string($contents)) {
                throw new RuntimeException(sprintf('Expected %s to be readable.', $relativePath));
            }

            return [$relativePath => $contents];
        })
        ->reject(fn (string $contents, string $relativePath): bool => collect($allowedIntegrationPaths)
            ->contains(fn (string $allowedIntegrationPath): bool => str_starts_with($relativePath, $allowedIntegrationPath)))
        ->flatMap(function (string $contents, string $relativePath) use ($optionalImports, $optionalClassConstants): array {
            $matches = [];

            foreach ([...$optionalImports, ...$optionalClassConstants] as $needle) {
                if (str_contains($contents, $needle)) {
                    $matches[] = sprintf('%s contains %s', $relativePath, $needle);
                }
            }

            return $matches;
        })
        ->sort()
        ->values()
        ->all();

    expect($violations)->toBe([]);
});
