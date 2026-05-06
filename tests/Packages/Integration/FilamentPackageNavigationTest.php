<?php

declare(strict_types=1);

use Capell\Blog\Filament\Resources\Articles\ArticleResource;
use Capell\CampaignStudio\Filament\Resources\CampaignConversionGoals\CampaignConversionGoalResource;
use Capell\CampaignStudio\Filament\Resources\CampaignCtaBlocks\CampaignCtaBlockResource;
use Capell\CampaignStudio\Filament\Resources\CampaignGroups\CampaignGroupResource;
use Capell\CampaignStudio\Filament\Resources\CampaignLandingPages\CampaignLandingPageResource;
use Capell\Core\Facades\CapellCore;
use Capell\Diagnostics\Filament\Pages\DiagnosticsPage;
use Capell\Diagnostics\Filament\Pages\PermissionAuditPage;
use Capell\Diagnostics\Filament\Pages\QueueHealthPage;
use Capell\Diagnostics\Filament\Pages\SystemHealthPage;
use Capell\MediaLibrary\Filament\Pages\MediaHealthPage;
use Capell\MigrationAssistant\Filament\Resources\ImportSessions\ImportSessionResource;
use Capell\Redirects\Filament\Resources\Redirects\RedirectResource;
use Capell\SeoSuite\Filament\Pages\BrokenLinksPage;
use Capell\SeoSuite\Filament\Pages\NotFoundUrlsPage;
use Capell\SeoSuite\Filament\Pages\SeoAuditPage;
use Capell\SeoSuite\Filament\Pages\TranslationCoveragePage;
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
        'capell-app/insights',
        'capell-app/login-audit',
        'capell-app/migration-assistant',
        'capell-app/blog',
        'capell-app/campaign-studio',
        'capell-app/block-library',
        'capell-app/content-sections',
        'capell-app/core',
        'capell-app/diagnostics',
        'capell-app/starter-sites',
        'capell-app/admin-preview',
        'capell-app/form-builder',
        'capell-app/frontend',
        'capell-app/frontend-authoring',
        'capell-app/media-library',
        'capell-app/layout-builder',
        'capell-app/navigation',
        'capell-app/redirects',
        'capell-app/seo-suite',
        'capell-app/search',
        'capell-app/tags',
        'capell-app/theme-studio-admin',
        'capell-app/theme-studio-core',
        'capell-app/publishing-studio',
    ];

    foreach (['capell-app/installer', 'capell-app/marketplace', 'capell-app/dashboard-reports'] as $optionalPackage) {
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
        DiagnosticsPage::class,
        MediaHealthPage::class,
        NotFoundUrlsPage::class,
        PermissionAuditPage::class,
        QueueHealthPage::class,
        SeoAuditPage::class,
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
        'Diagnostics',
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
