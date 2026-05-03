<?php

declare(strict_types=1);

use Capell\SeoTools\Contracts\SeoPublishReportProvider;
use Capell\SeoTools\Support\Publishing\SeoPublishReportProviderAdapter;
use Symfony\Component\Finder\Finder;

arch('seo-tools does not import packages that depend on it')
    ->expect('Capell\SeoTools')
    ->not->toUse([
        'Capell\Address',
        'Capell\Blog',
        'Capell\Forms',
        'Capell\Media',
        'Capell\Mosaic',
        'Capell\Navigation',
        'Capell\Marketplace',
        'Capell\Tags',
        'Capell\Themes',
        'Capell\Workspaces',
    ])
    ->ignoring([
        SeoPublishReportProvider::class,
        SeoPublishReportProviderAdapter::class,
    ]);

it('keeps workspaces references inside the publish report bridge', function (): void {
    $packagePath = dirname(__DIR__, 2);
    $allowedPaths = [
        'src/Contracts/SeoPublishReportProvider.php',
        'src/Support/Publishing/SeoPublishReportProviderAdapter.php',
    ];
    $violations = [];

    $files = (new Finder)
        ->files()
        ->in($packagePath . '/src')
        ->name('*.php');

    foreach ($files as $file) {
        $relativePath = str_replace($packagePath . '/', '', $file->getPathname());

        if (! str_contains($file->getContents(), 'Capell\\Workspaces')) {
            continue;
        }

        if (in_array($relativePath, $allowedPaths, true)) {
            continue;
        }

        $violations[] = $relativePath;
    }

    expect($violations)->toBeEmpty();
});

it('keeps seo report builders independent of plugin internals', function (): void {
    $packagePath = dirname(__DIR__, 2);
    $reportPaths = [
        'src/Actions/BuildPageSeoReportAction.php',
        'src/Actions/CalculateSeoScoreAction.php',
        'src/Actions/SuggestInternalLinksAction.php',
        'src/Actions/BuildSchemaTemplateReportAction.php',
        'src/Actions/BuildPageSearchConsoleInsightsAction.php',
        'src/Data/PageSeoReportData.php',
    ];
    $forbiddenNamespaces = [
        'Capell\\Blog',
        'Capell\\Tags',
        'Capell\\SiteSearch',
        'Capell\\Workspaces',
    ];
    $violations = [];

    foreach ($reportPaths as $reportPath) {
        $contents = file_get_contents($packagePath . '/' . $reportPath);

        expect($contents)->not->toBeFalse();

        foreach ($forbiddenNamespaces as $namespace) {
            if (! str_contains((string) $contents, $namespace)) {
                continue;
            }

            $violations[] = sprintf('%s references %s', $reportPath, $namespace);
        }
    }

    expect($violations)->toBeEmpty();
});

arch()
    ->expect('Capell\SeoTools')
    ->classes()
    ->toUseStrictEquality();
