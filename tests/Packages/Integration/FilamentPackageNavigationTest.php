<?php

declare(strict_types=1);

use Capell\Blog\Filament\Resources\Articles\ArticleResource;
use Capell\Campaigns\Filament\Resources\CampaignConversionGoals\CampaignConversionGoalResource;
use Capell\Campaigns\Filament\Resources\CampaignCtaBlocks\CampaignCtaBlockResource;
use Capell\Campaigns\Filament\Resources\CampaignGroups\CampaignGroupResource;
use Capell\Campaigns\Filament\Resources\CampaignLandingPages\CampaignLandingPageResource;
use Capell\Core\Facades\CapellCore;
use Capell\DeveloperTools\Filament\Pages\DeveloperToolsPage;
use Capell\DeveloperTools\Filament\Pages\PermissionAuditPage;
use Capell\DeveloperTools\Filament\Pages\QueueHealthPage;
use Capell\DeveloperTools\Filament\Pages\SystemHealthPage;
use Capell\MediaCurator\Filament\Pages\MediaHealthPage;
use Capell\Migrator\Filament\Resources\ImportSessions\ImportSessionResource;
use Capell\Redirects\Filament\Resources\Redirects\RedirectResource;
use Capell\SeoTools\Filament\Pages\BrokenLinksPage;
use Capell\SeoTools\Filament\Pages\NotFoundUrlsPage;
use Capell\SeoTools\Filament\Pages\SEOAuditPage;
use Capell\SeoTools\Filament\Pages\TranslationCoveragePage;
use Capell\Tags\Filament\Resources\Tags\TagResource;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\ThemeStudio\Admin\Filament\Pages\ThemeStudioPage;
use Composer\InstalledVersions;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;

uses(CreatesAdminUser::class);

it('registers every installed package in the Capell package registry', function (): void {
    $expectedPackages = [
        'capell-app/address',
        'capell-app/admin',
        'capell-app/analytics',
        'capell-app/authentication-log',
        'capell-app/migrator',
        'capell-app/blog',
        'capell-app/campaigns',
        'capell-app/content-blocks',
        'capell-app/core',
        'capell-app/developer-tools',
        'capell-app/example-sites',
        'capell-app/filament-peek',
        'capell-app/forms',
        'capell-app/frontend',
        'capell-app/frontend-toolbar',
        'capell-app/media-curator',
        'capell-app/mosaic',
        'capell-app/navigation',
        'capell-app/redirects',
        'capell-app/seo-tools',
        'capell-app/site-search',
        'capell-app/tags',
        'capell-app/theme-studio-admin',
        'capell-app/theme-studio-core',
        'capell-app/workspaces',
    ];

    foreach (['capell-app/installer', 'capell-app/marketplace', 'capell-app/reports'] as $optionalPackage) {
        if (InstalledVersions::isInstalled($optionalPackage)) {
            $expectedPackages[] = $optionalPackage;
        }
    }

    expect(CapellCore::getInstalledPackages()->keys()->all())
        ->toEqualCanonicalizing($expectedPackages);
});

it('registers installed package admin surfaces before the Filament navigation is built', function (): void {
    test()->actingAsAdmin();

    $expectedResources = [
        ArticleResource::class,
        CampaignConversionGoalResource::class,
        CampaignCtaBlockResource::class,
        CampaignGroupResource::class,
        CampaignLandingPageResource::class,
        ImportSessionResource::class,
        RedirectResource::class,
        TagResource::class,
    ];

    $expectedPages = [
        BrokenLinksPage::class,
        DeveloperToolsPage::class,
        MediaHealthPage::class,
        NotFoundUrlsPage::class,
        PermissionAuditPage::class,
        QueueHealthPage::class,
        SEOAuditPage::class,
        SystemHealthPage::class,
        ThemeStudioPage::class,
        TranslationCoveragePage::class,
    ];

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();

    expect(Filament::getResources())->toContain(...$expectedResources);
    expect(Filament::getPages())->toContain(...$expectedPages);
    expect(collect($expectedResources)->every(fn (string $resource): bool => $resource::canViewAny()))->toBeTrue();
    expect(collect($expectedPages)->every(fn (string $page): bool => $page::canAccess()))->toBeTrue();
});

it('shows installed package admin surfaces in Filament navigation', function (): void {
    test()->actingAsAdmin();

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    Filament::setServingStatus();

    $navigationLabels = collect(Filament::getNavigation())
        ->flatMap(fn (NavigationGroup $group): array => collect($group->getItems())->all())
        ->map(fn ($item): string => $item->getLabel())
        ->all();

    expect($navigationLabels)->toContain(
        'Articles',
        'Broken Links',
        'Campaign groups',
        'CTA blocks',
        'Conversion goals',
        'Developer Tools',
        'Import Sessions',
        'Landing pages',
        'Media Health',
        'Missing Pages',
        'Permission Audit',
        'Queue Health',
        'Redirects',
        'SEO Audit',
        'System Health',
        'Tags',
        'Theme Studio',
        'Translation Coverage',
    );
});
