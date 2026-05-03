<?php

declare(strict_types=1);

use Capell\Address\Filament\Resources\Addresses\AddressResource;
use Capell\Address\Filament\Resources\Countries\CountryResource;
use Capell\Address\Models\Address;
use Capell\Address\Models\Country;
use Capell\Address\Providers\AddressServiceProvider;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Analytics\Filament\Widgets\AnalyticsOverviewStatsWidget;
use Capell\Analytics\Models\AnalyticsConsent;
use Capell\Analytics\Models\AnalyticsEvent;
use Capell\Analytics\Models\AnalyticsVisit;
use Capell\Analytics\Providers\AnalyticsServiceProvider;
use Capell\AuthenticationLog\Filament\Resources\AuthenticationLogs\AuthenticationLogResource;
use Capell\AuthenticationLog\Filament\Widgets\AuthenticationLogsWidget;
use Capell\AuthenticationLog\Models\AuthenticationLog;
use Capell\AuthenticationLog\Providers\AuthenticationLogServiceProvider;
use Capell\Backup\Contracts\BackupContextResolver;
use Capell\Backup\Filament\Resources\ImportSessions\ImportSessionResource;
use Capell\Backup\Models\BackupRestore;
use Capell\Backup\Models\ImportSession;
use Capell\Backup\Providers\BackupServiceProvider;
use Capell\Blog\Filament\Resources\Articles\ArticleResource;
use Capell\Blog\Models\Article;
use Capell\Blog\Providers\BlogServiceProvider;
use Capell\Campaigns\Filament\Resources\CampaignConversionGoals\CampaignConversionGoalResource;
use Capell\Campaigns\Filament\Resources\CampaignCtaBlocks\CampaignCtaBlockResource;
use Capell\Campaigns\Filament\Resources\CampaignGroups\CampaignGroupResource;
use Capell\Campaigns\Filament\Resources\CampaignLandingPages\CampaignLandingPageResource;
use Capell\Campaigns\Filament\Widgets\CampaignOverviewStatsWidget;
use Capell\Campaigns\Models\CampaignConversion;
use Capell\Campaigns\Models\CampaignConversionGoal;
use Capell\Campaigns\Models\CampaignCtaBlock;
use Capell\Campaigns\Models\CampaignGroup;
use Capell\Campaigns\Models\CampaignLandingPage;
use Capell\Campaigns\Providers\CampaignsServiceProvider;
use Capell\ContentBlocks\Filament\Resources\ContentBlocks\ContentBlockResource;
use Capell\ContentBlocks\Models\ContentBlock;
use Capell\ContentBlocks\Providers\ContentBlocksServiceProvider;
use Capell\Core\Enums\VendorAssetEnum;
use Capell\Core\Facades\CapellCore;
use Capell\DeveloperTools\Filament\Pages\DeveloperToolsPage;
use Capell\DeveloperTools\Filament\Widgets\Health\SiteHealthWidgetAbstract;
use Capell\DeveloperTools\Providers\DeveloperToolsServiceProvider;
use Capell\FilamentPeek\Providers\FilamentPeekServiceProvider;
use Capell\Forms\Models\Form;
use Capell\Forms\Models\Submission;
use Capell\Forms\Providers\FormsServiceProvider;
use Capell\Frontend\Contracts\HtmlMinifier;
use Capell\HtmlMinify\Providers\HtmlMinifyServiceProvider;
use Capell\HtmlMinify\Support\Html\HtmlMinifier as VokuHtmlMinifier;
use Capell\Mcp\Filament\Pages\CapellMcpPromptBuilderPage;
use Capell\Mcp\Providers\CapellMcpServiceProvider;
use Capell\Mcp\Support\CapellMcpCapabilityRegistry;
use Capell\MediaCurator\CapellMediaCuratorServiceProvider;
use Capell\MediaCurator\Filament\Pages\MediaHealthPage;
use Capell\MediaCurator\Models\CuratorMedia;
use Capell\Mosaic\Filament\Resources\Layouts\LayoutResource;
use Capell\Mosaic\Filament\Resources\Sections\SectionResource;
use Capell\Mosaic\Filament\Resources\Widgets\WidgetResource;
use Capell\Mosaic\Models\Section;
use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Models\WidgetAsset;
use Capell\Mosaic\Providers\MosaicServiceProvider;
use Capell\Navigation\Filament\Resources\Navigations\NavigationResource;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Providers\NavigationServiceProvider;
use Capell\Redirects\Contracts\RedirectResolver;
use Capell\Redirects\Filament\Resources\Redirects\RedirectResource;
use Capell\Redirects\Providers\RedirectsServiceProvider;
use Capell\SeoTools\Filament\Pages\BrokenLinksPage;
use Capell\SeoTools\Filament\Pages\NotFoundUrlsPage;
use Capell\SeoTools\Filament\Pages\SEOAuditPage;
use Capell\SeoTools\Models\AIGenerationHistory;
use Capell\SeoTools\Models\BrokenLink;
use Capell\SeoTools\Models\PageSeoSnapshot;
use Capell\SeoTools\Providers\SeoToolsServiceProvider;
use Capell\SiteSearch\Contracts\SiteSearch;
use Capell\SiteSearch\Filament\Widgets\SearchOverviewStatsWidget;
use Capell\SiteSearch\Models\SiteSearchLog;
use Capell\SiteSearch\Providers\SiteSearchServiceProvider;
use Capell\Tags\Filament\Resources\Tags\TagResource;
use Capell\Tags\Models\Tag;
use Capell\Tags\Providers\TagsServiceProvider;
use Capell\ThemeStudio\Admin\Filament\Pages\ThemeStudioPage;
use Capell\ThemeStudio\Admin\ThemeStudioAdminServiceProvider;
use Capell\ThemeStudio\Core\Theme\ThemeRegistry;
use Capell\ThemeStudio\Core\ThemeStudioCoreServiceProvider;
use Capell\Toolbar\Providers\ToolbarServiceProvider;
use Capell\Workspaces\Filament\Resources\PreviewLinks\PreviewLinkResource;
use Capell\Workspaces\Filament\Resources\Workspaces\WorkspaceResource;
use Capell\Workspaces\Models\PreviewLink;
use Capell\Workspaces\Models\Version;
use Capell\Workspaces\Models\Workspace;
use Capell\Workspaces\Providers\WorkspacesServiceProvider;
use Illuminate\Support\Facades\Route;

it('discovers composer-required packages without treating them as installed Capell plugins', function (): void {
    $composerRequiredPackages = [
        BackupServiceProvider::$packageName,
        HtmlMinifyServiceProvider::$packageName,
        CapellMcpServiceProvider::$packageName,
    ];

    $composer = json_decode((string) file_get_contents(dirname(__DIR__, 3) . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    $requiredPackageNames = array_values(array_filter(
        $composerRequiredPackages,
        fn (string $packageName): bool => array_key_exists($packageName, $composer['require']),
    ));

    foreach ($requiredPackageNames as $packageName) {
        expect($composer['require'])->toHaveKey($packageName)
            ->and(CapellCore::hasPackage($packageName))->toBeTrue()
            ->and(CapellCore::isPackageInstalled($packageName))->toBeFalse();
    }

    expect(CapellCore::getInstalledPackages()->keys()->all())->not->toContain(...$composerRequiredPackages);
});

it('registers package metadata but skips runtime models, tables, settings, and assets for uninstalled packages', function (): void {
    $packageNames = [
        AddressServiceProvider::$packageName,
        AnalyticsServiceProvider::$packageName,
        AuthenticationLogServiceProvider::$packageName,
        BackupServiceProvider::$packageName,
        BlogServiceProvider::$packageName,
        CampaignsServiceProvider::$packageName,
        ContentBlocksServiceProvider::$packageName,
        DeveloperToolsServiceProvider::$packageName,
        FilamentPeekServiceProvider::$packageName,
        FormsServiceProvider::$packageName,
        CapellMediaCuratorServiceProvider::$packageName,
        MosaicServiceProvider::$packageName,
        NavigationServiceProvider::$packageName,
        RedirectsServiceProvider::$packageName,
        SeoToolsServiceProvider::$packageName,
        SiteSearchServiceProvider::$packageName,
        TagsServiceProvider::$packageName,
        ThemeStudioAdminServiceProvider::$packageName,
        ThemeStudioCoreServiceProvider::$packageName,
        ToolbarServiceProvider::$packageName,
        WorkspacesServiceProvider::$packageName,
    ];

    foreach ($packageNames as $packageName) {
        expect(CapellCore::hasPackage($packageName))->toBeTrue($packageName . ' was not registered for discovery')
            ->and(CapellCore::isPackageInstalled($packageName))->toBeFalse($packageName . ' was treated as installed');
    }

    expect(CapellCore::getModels())->not->toContain(
        Address::class,
        Country::class,
        AnalyticsConsent::class,
        AnalyticsEvent::class,
        AnalyticsVisit::class,
        AuthenticationLog::class,
        BackupRestore::class,
        ImportSession::class,
        Article::class,
        CampaignConversion::class,
        CampaignConversionGoal::class,
        CampaignCtaBlock::class,
        CampaignGroup::class,
        CampaignLandingPage::class,
        ContentBlock::class,
        Form::class,
        Submission::class,
        CuratorMedia::class,
        Section::class,
        Widget::class,
        WidgetAsset::class,
        Navigation::class,
        AIGenerationHistory::class,
        BrokenLink::class,
        PageSeoSnapshot::class,
        SiteSearchLog::class,
        Tag::class,
        PreviewLink::class,
        Version::class,
        Workspace::class,
    );

    expect(CapellCore::getProtectedTables())->not->toContain(
        'analytics_visits',
        'analytics_consents',
        'analytics_events',
        'authentication_log',
        'site_search_logs',
    );

    $tailwindSources = CapellCore::getVendorAssetsForType(VendorAssetEnum::TailwindSource)
        ->map(fn ($asset): ?string => $asset->packageName)
        ->all();

    expect($tailwindSources)->not->toContain(
        AddressServiceProvider::$packageName,
        BlogServiceProvider::$packageName,
        FormsServiceProvider::$packageName,
        MosaicServiceProvider::$packageName,
    );
});

it('does not expose admin resources, pages, widgets, or routes for uninstalled packages', function (): void {
    $adminSurfaceRegistry = CapellAdmin::getAdminSurfaceRegistry();

    expect($adminSurfaceRegistry->resources())->not->toContain(
        AddressResource::class,
        CountryResource::class,
        ArticleResource::class,
        CampaignConversionGoalResource::class,
        CampaignCtaBlockResource::class,
        CampaignGroupResource::class,
        CampaignLandingPageResource::class,
        ContentBlockResource::class,
        'Capell\\Forms\\Filament\\Resources\\Forms\\FormResource',
        'Capell\\Forms\\Filament\\Resources\\Submissions\\SubmissionResource',
        ImportSessionResource::class,
        LayoutResource::class,
        SectionResource::class,
        WidgetResource::class,
        NavigationResource::class,
        RedirectResource::class,
        TagResource::class,
        WorkspaceResource::class,
        PreviewLinkResource::class,
    );

    expect($adminSurfaceRegistry->resources())->not->toContain(AuthenticationLogResource::class);

    expect($adminSurfaceRegistry->pages())->not->toContain(
        BrokenLinksPage::class,
        CapellMcpPromptBuilderPage::class,
        DeveloperToolsPage::class,
        MediaHealthPage::class,
        NotFoundUrlsPage::class,
        SEOAuditPage::class,
        ThemeStudioPage::class,
    );

    expect(CapellAdmin::getDashboardWidgets(DashboardEnum::Main))->not->toContain(
        AnalyticsOverviewStatsWidget::class,
        CampaignOverviewStatsWidget::class,
        SiteHealthWidgetAbstract::class,
        SearchOverviewStatsWidget::class,
    );

    expect(CapellAdmin::getDashboardWidgets(DashboardEnum::SystemHealth))->not->toContain(AuthenticationLogsWidget::class);

    expect(Route::getMiddleware())->not->toHaveKey('frontend.minify')
        ->and(Route::getMiddleware())->not->toHaveKey('frontend.activity')
        ->and(Route::has('capell-mcp.server'))->toBeFalse();
});

it('does not bind package runtime services for uninstalled packages', function (): void {
    expect(app()->bound(BackupContextResolver::class))->toBeFalse()
        ->and(app()->bound(RedirectResolver::class))->toBeFalse()
        ->and(app()->bound(SiteSearch::class))->toBeFalse()
        ->and(app()->bound(CapellMcpCapabilityRegistry::class))->toBeFalse()
        ->and(app()->bound(ThemeRegistry::class))->toBeFalse();

    expect(resolve(HtmlMinifier::class))->not->toBeInstanceOf(VokuHtmlMinifier::class);

    expect(CapellCore::getPageTypes()->keys()->all())->not->toContain(
        'article',
        'blog',
        'content-block',
        'form',
        'sitemap',
        'thank-you',
    );
});
