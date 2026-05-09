<?php

declare(strict_types=1);

use Capell\SeoSuite\Contracts\SeoPublishReportProvider;
use Capell\SeoSuite\Support\Publishing\SeoPublishReportProviderAdapter;
use Symfony\Component\Finder\Finder;

arch('seo-suite does not import packages that depend on it')
    ->expect('Capell\SeoSuite')
    ->not->toUse([
        'Capell\Address',
        'Capell\Blog',
        'Capell\FormBuilder',
        'Capell\Media',
        'Capell\Core\LayoutBuilder',
        'Capell\Navigation',
        'Capell\Marketplace',
        'Capell\Tags',
        'Capell\Themes',
        'Capell\PublishingStudio',
    ])
    ->ignoring([
        SeoPublishReportProvider::class,
        SeoPublishReportProviderAdapter::class,
    ]);

it('keeps publishing-studio references inside the publish report bridge', function (): void {
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

        if (! str_contains($file->getContents(), 'Capell\\PublishingStudio')) {
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
        'Capell\\Search',
        'Capell\\PublishingStudio',
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

it('uses only site discovery public discovery APIs', function (): void {
    $packagePath = dirname(__DIR__, 2);
    $allowedPrefixes = [
        'Capell\\SiteDiscovery\\Actions\\DiscoverPublicPagesAction',
        'Capell\\SiteDiscovery\\Actions\\DiscoverPublicUrlsAction',
        'Capell\\SiteDiscovery\\Contracts\\DiscoverableUrlSource',
        'Capell\\SiteDiscovery\\Data\\DiscoverablePageData',
        'Capell\\SiteDiscovery\\Data\\DiscoverableUrlData',
        'Capell\\SiteDiscovery\\Providers\\SiteDiscoveryServiceProvider',
    ];
    $violations = [];

    $files = (new Finder)
        ->files()
        ->in($packagePath . '/src')
        ->name('*.php');

    foreach ($files as $file) {
        $contents = $file->getContents();

        if (! str_contains($contents, 'Capell\\SiteDiscovery\\')) {
            continue;
        }

        foreach ($allowedPrefixes as $allowedPrefix) {
            if (str_contains($contents, $allowedPrefix)) {
                continue 2;
            }
        }

        $violations[] = str_replace($packagePath . '/', '', $file->getPathname());
    }

    expect($violations)->toBeEmpty();
});

arch()
    ->expect('Capell\SeoSuite')
    ->classes()
    ->toUseStrictEquality();
