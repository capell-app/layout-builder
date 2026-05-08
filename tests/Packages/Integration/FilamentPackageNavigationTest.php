<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Redirects\RedirectResource;
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
use Capell\SeoSuite\Filament\Pages\BrokenLinksPage;
use Capell\SeoSuite\Filament\Pages\NotFoundUrlsPage;
use Capell\SeoSuite\Filament\Pages\SeoAuditPage;
use Capell\SeoSuite\Filament\Pages\TranslationCoveragePage;
use Capell\Tags\Filament\Resources\Tags\TagResource;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Composer\InstalledVersions;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;

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
        'capell-app/content-blocks',
        'capell-app/content-sections',
        'capell-app/core',
        'capell-app/diagnostics',
        'capell-app/demo-kit',
        'capell-app/form-builder',
        'capell-app/frontend',
        'capell-app/frontend-authoring',
        'capell-app/media-library',
        'capell-app/navigation',
        'capell-app/seo-suite',
        'capell-app/search',
        'capell-app/tags',
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

    $navigationItems = static function (NavigationItem $navigationItem) use (&$navigationItems): array {
        return [
            $navigationItem,
            ...collect($navigationItem->getChildItems())
                ->flatMap(fn (NavigationItem $childNavigationItem): array => $navigationItems($childNavigationItem))
                ->all(),
        ];
    };

    $navigationLabels = collect(Filament::getNavigation())
        ->flatMap(fn (NavigationGroup $group): array => collect($group->getItems())
            ->flatMap(fn (NavigationItem $navigationItem): array => $navigationItems($navigationItem))
            ->all())
        ->map(fn (NavigationItem $navigationItem): string => $navigationItem->getLabel())
        ->all();

    expect($navigationLabels)->toContain(
        'Articles',
        'Campaign groups',
        'CTA blocks',
        'Conversion goals',
        'Import Sessions',
        'Landing pages',
        (string) __('capell-admin::navigation.redirects'),
        'Tags',
    );
});

it('places extension-owned package pages under extensions navigation', function (): void {
    test()->actingAsAdmin();

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    Filament::setServingStatus();

    $navigationGroups = collect(Filament::getNavigation());
    $extensionItems = $navigationGroups
        ->filter(fn (NavigationGroup $group): bool => $group->getLabel() === __('capell-admin::navigation.group_extensions'))
        ->flatMap(fn (NavigationGroup $group): array => collect($group->getItems())->all())
        ->all();
    $systemItems = $navigationGroups
        ->filter(fn (NavigationGroup $group): bool => $group->getLabel() === __('capell-admin::navigation.group_administration'))
        ->flatMap(fn (NavigationGroup $group): array => collect($group->getItems())->all())
        ->all();

    $extensionLabels = collect($extensionItems)->map(fn ($item): string => $item->getLabel())->all();
    $systemLabels = collect($systemItems)->map(fn ($item): string => $item->getLabel())->all();

    $extensionPageLabels = [
        'capell-admin::navigation.media_health',
        'capell-admin::navigation.permission_audit',
        'capell-admin::navigation.queue_health',
        'capell-admin::navigation.system_health',
        'Diagnostics',
    ];

    expect($extensionLabels)->not->toContain(...$extensionPageLabels)
        ->and($systemLabels)->not->toContain(...$extensionPageLabels);
});
