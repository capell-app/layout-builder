<?php

declare(strict_types=1);

use Capell\Address\Filament\Resources\Addresses\AddressResource;
use Capell\Address\Filament\Resources\Countries\CountryResource;
use Capell\Address\Models\Address;
use Capell\Address\Models\Country;
use Capell\Address\Providers\AddressServiceProvider;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\AdminPreview\Providers\AdminPreviewServiceProvider;
use Capell\AgentBridge\Filament\Pages\CapellAgentBridgePromptBuilderPage;
use Capell\AgentBridge\Providers\AgentBridgeServiceProvider;
use Capell\AgentBridge\Support\CapellAgentBridgeCapabilityRegistry;
use Capell\Blog\Filament\Resources\Articles\ArticleResource;
use Capell\Blog\Models\Article;
use Capell\Blog\Providers\BlogServiceProvider;
use Capell\CampaignStudio\Filament\Resources\CampaignConversionGoals\CampaignConversionGoalResource;
use Capell\CampaignStudio\Filament\Resources\CampaignCtaBlocks\CampaignCtaBlockResource;
use Capell\CampaignStudio\Filament\Resources\CampaignGroups\CampaignGroupResource;
use Capell\CampaignStudio\Filament\Resources\CampaignLandingPages\CampaignLandingPageResource;
use Capell\CampaignStudio\Filament\Widgets\CampaignOverviewStatsWidget;
use Capell\CampaignStudio\Models\CampaignConversion;
use Capell\CampaignStudio\Models\CampaignConversionGoal;
use Capell\CampaignStudio\Models\CampaignCtaBlock;
use Capell\CampaignStudio\Models\CampaignGroup;
use Capell\CampaignStudio\Models\CampaignLandingPage;
use Capell\CampaignStudio\Providers\CampaignStudioServiceProvider;
use Capell\ContentSections\Filament\Resources\Sections\SectionResource;
use Capell\ContentSections\Models\Section;
use Capell\Core\Enums\VendorAssetEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Diagnostics\Filament\Pages\DiagnosticsPage;
use Capell\Diagnostics\Filament\Widgets\Health\SiteHealthWidgetAbstract;
use Capell\Diagnostics\Providers\DiagnosticsServiceProvider;
use Capell\FormBuilder\Models\Form;
use Capell\FormBuilder\Models\Submission;
use Capell\FormBuilder\Providers\FormBuilderServiceProvider;
use Capell\Frontend\Contracts\HtmlMinifier;
use Capell\FrontendAuthoring\Providers\FrontendAuthoringServiceProvider;
use Capell\HtmlOptimizer\Providers\HtmlOptimizerServiceProvider;
use Capell\HtmlOptimizer\Support\Html\HtmlMinifier as VokuHtmlMinifier;
use Capell\Insights\Filament\Widgets\InsightsOverviewStatsWidget;
use Capell\Insights\Models\InsightsConsent;
use Capell\Insights\Models\InsightsEvent;
use Capell\Insights\Models\InsightsVisit;
use Capell\Insights\Providers\InsightsServiceProvider;
use Capell\LayoutBuilder\Filament\Resources\Layouts\LayoutResource;
use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Providers\LayoutBuilderServiceProvider;
use Capell\LoginAudit\Filament\Resources\LoginAudits\LoginAuditResource;
use Capell\LoginAudit\Filament\Widgets\LoginAuditsWidget;
use Capell\LoginAudit\Models\LoginAudit;
use Capell\LoginAudit\Providers\LoginAuditServiceProvider;
use Capell\MediaLibrary\Filament\Pages\MediaHealthPage;
use Capell\MediaLibrary\MediaLibraryServiceProvider;
use Capell\MediaLibrary\Models\CuratorMedia;
use Capell\MigrationAssistant\Contracts\MigrationAssistantContextResolver;
use Capell\MigrationAssistant\Filament\Resources\ImportSessions\ImportSessionResource;
use Capell\MigrationAssistant\Models\ImportRollbackReport;
use Capell\MigrationAssistant\Models\ImportSession;
use Capell\MigrationAssistant\Providers\MigrationAssistantServiceProvider;
use Capell\Navigation\Filament\Resources\Navigations\NavigationResource;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Providers\NavigationServiceProvider;
use Capell\PublishingStudio\Filament\Resources\PreviewLinks\PreviewLinkResource;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\WorkspaceResource;
use Capell\PublishingStudio\Models\PreviewLink;
use Capell\PublishingStudio\Models\Version;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\Providers\PublishingStudioServiceProvider;
use Capell\Redirects\Contracts\RedirectResolver;
use Capell\Redirects\Filament\Resources\Redirects\RedirectResource;
use Capell\Redirects\Providers\RedirectsServiceProvider;
use Capell\Search\Contracts\Search;
use Capell\Search\Filament\Widgets\SearchOverviewStatsWidget;
use Capell\Search\Models\SearchLog;
use Capell\Search\Providers\SearchServiceProvider;
use Capell\SeoSuite\Filament\Pages\BrokenLinksPage;
use Capell\SeoSuite\Filament\Pages\NotFoundUrlsPage;
use Capell\SeoSuite\Filament\Pages\SeoAuditPage;
use Capell\SeoSuite\Models\AIGenerationHistory;
use Capell\SeoSuite\Models\BrokenLink;
use Capell\SeoSuite\Models\PageSeoSnapshot;
use Capell\SeoSuite\Providers\SeoSuiteServiceProvider;
use Capell\Tags\Filament\Resources\Tags\TagResource;
use Capell\Tags\Models\Tag;
use Capell\Tags\Providers\TagsServiceProvider;
use Illuminate\Support\Facades\Route;

it('discovers composer-required packages without treating them as installed Capell plugins', function (): void {
    $composerRequiredPackages = [
        MigrationAssistantServiceProvider::$packageName,
        HtmlOptimizerServiceProvider::$packageName,
        AgentBridgeServiceProvider::$packageName,
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
        InsightsServiceProvider::$packageName,
        LoginAuditServiceProvider::$packageName,
        MigrationAssistantServiceProvider::$packageName,
        BlogServiceProvider::$packageName,
        CampaignStudioServiceProvider::$packageName,
        DiagnosticsServiceProvider::$packageName,
        AdminPreviewServiceProvider::$packageName,
        FormBuilderServiceProvider::$packageName,
        MediaLibraryServiceProvider::$packageName,
        LayoutBuilderServiceProvider::$packageName,
        NavigationServiceProvider::$packageName,
        RedirectsServiceProvider::$packageName,
        SeoSuiteServiceProvider::$packageName,
        SearchServiceProvider::$packageName,
        TagsServiceProvider::$packageName,
        FrontendAuthoringServiceProvider::$packageName,
        PublishingStudioServiceProvider::$packageName,
    ];

    foreach ($packageNames as $packageName) {
        expect(CapellCore::hasPackage($packageName))->toBeTrue($packageName . ' was not registered for discovery')
            ->and(CapellCore::isPackageInstalled($packageName))->toBeFalse($packageName . ' was treated as installed');
    }

    expect(CapellCore::getModels())->not->toContain(
        Address::class,
        Country::class,
        InsightsConsent::class,
        InsightsEvent::class,
        InsightsVisit::class,
        LoginAudit::class,
        ImportRollbackReport::class,
        ImportSession::class,
        Article::class,
        CampaignConversion::class,
        CampaignConversionGoal::class,
        CampaignCtaBlock::class,
        CampaignGroup::class,
        CampaignLandingPage::class,
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
        SearchLog::class,
        Tag::class,
        PreviewLink::class,
        Version::class,
        Workspace::class,
    );

    expect(CapellCore::getProtectedTables())->not->toContain(
        'insights_visits',
        'insights_consents',
        'insights_events',
        'login_audit',
        'search_logs',
    );

    $tailwindSources = CapellCore::getVendorAssetsForType(VendorAssetEnum::TailwindSource)
        ->map(fn ($asset): ?string => $asset->packageName)
        ->all();

    expect($tailwindSources)->not->toContain(
        AddressServiceProvider::$packageName,
        BlogServiceProvider::$packageName,
        FormBuilderServiceProvider::$packageName,
        LayoutBuilderServiceProvider::$packageName,
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
        'Capell\\FormBuilder\\Filament\\Resources\\FormBuilder\\FormResource',
        'Capell\\FormBuilder\\Filament\\Resources\\Submissions\\SubmissionResource',
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

    expect($adminSurfaceRegistry->resources())->not->toContain(LoginAuditResource::class);

    expect($adminSurfaceRegistry->pages())->not->toContain(
        BrokenLinksPage::class,
        CapellAgentBridgePromptBuilderPage::class,
        DiagnosticsPage::class,
        MediaHealthPage::class,
        NotFoundUrlsPage::class,
        SeoAuditPage::class,
    );

    expect(CapellAdmin::getDashboardWidgets(DashboardEnum::Main))->not->toContain(
        InsightsOverviewStatsWidget::class,
        CampaignOverviewStatsWidget::class,
        SiteHealthWidgetAbstract::class,
        SearchOverviewStatsWidget::class,
    );

    expect(CapellAdmin::getDashboardWidgets(DashboardEnum::SystemHealth))->not->toContain(LoginAuditsWidget::class);

    expect(Route::getMiddleware())->not->toHaveKey('frontend.minify')
        ->and(Route::getMiddleware())->not->toHaveKey('frontend.activity')
        ->and(Route::has('capell-agent-bridge.server'))->toBeFalse();
});

it('does not bind package runtime services for uninstalled packages', function (): void {
    expect(app()->bound(MigrationAssistantContextResolver::class))->toBeFalse()
        ->and(app()->bound(RedirectResolver::class))->toBeFalse()
        ->and(app()->bound(Search::class))->toBeFalse()
        ->and(app()->bound(CapellAgentBridgeCapabilityRegistry::class))->toBeFalse();

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
