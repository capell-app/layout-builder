<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Providers;

use Capell\Admin\Contracts\AdminTools\AdminToolItem;
use Capell\Admin\Contracts\Extenders\PageEditExtender;
use Capell\Admin\Contracts\Extenders\PageHeaderActionExtender;
use Capell\Admin\Contracts\Extenders\PageResourceWidgetExtender;
use Capell\Admin\Contracts\Extenders\PageSchemaExtender;
use Capell\Admin\Contracts\Extenders\ResourceHeaderActionExtender;
use Capell\Admin\Contracts\Extenders\SiteHeaderActionExtender;
use Capell\Admin\Contracts\Extenders\SiteRecordActionExtender;
use Capell\Admin\Contracts\Extenders\SiteSchemaExtender;
use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Admin\Support\AdminEventRegistry;
use Capell\Admin\Support\CapellAdminManager;
use Capell\Core\Actions\RegisterBlazeOptimizedViewsAction;
use Capell\Core\Data\PackageData;
use Capell\Core\Enums\PackageTypeEnum;
use Capell\Core\Enums\TypeEnum;
use Capell\Core\Events\PageDeleted;
use Capell\Core\Events\PageSaved;
use Capell\Core\Events\SiteCreated;
use Capell\Core\Events\UrlVisitFailed;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Core\Support\ContentGraph\ContentGraphRegistry;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsGroupMetadata;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\SeoSuite\Actions\Ai\RecordAiGenerationAction;
use Capell\SeoSuite\Console\Commands\ClearAiCacheCommand;
use Capell\SeoSuite\Console\Commands\InstallCommand;
use Capell\SeoSuite\Console\Commands\MonitorAiUsageCommand;
use Capell\SeoSuite\Console\Commands\SetupCommand;
use Capell\SeoSuite\Console\Commands\TestOpenAiConnectionCommand;
use Capell\SeoSuite\Console\Commands\XmlSitemapCommand;
use Capell\SeoSuite\Contracts\Schemas\SearchMetaDataSectionExtenderResolverInterface;
use Capell\SeoSuite\Contracts\SearchConsoleClientInterface;
use Capell\SeoSuite\Contracts\SeoPublishReportProvider;
use Capell\SeoSuite\Enums\SchemaTemplateTypeEnum;
use Capell\SeoSuite\Events\AiGenerationCompleted;
use Capell\SeoSuite\Events\AiGenerationFailed;
use Capell\SeoSuite\Filament\Extenders\Page\PageSeoPanelSchemaExtender;
use Capell\SeoSuite\Filament\Extenders\Page\PageSeoSettingsTabExtender;
use Capell\SeoSuite\Filament\Extenders\Page\SearchMetaSchemaExtender;
use Capell\SeoSuite\Filament\Extenders\Page\SitemapResourceHeaderActionExtender;
use Capell\SeoSuite\Filament\Extenders\Site\SiteDetailsMetaExtender;
use Capell\SeoSuite\Filament\Extenders\Site\SitemapSiteHeaderActionExtender;
use Capell\SeoSuite\Filament\Extenders\Site\SitemapSiteRecordActionExtender;
use Capell\SeoSuite\Filament\Extenders\Site\SiteTranslationMetaExtender;
use Capell\SeoSuite\Filament\Pages\BrokenLinksPage;
use Capell\SeoSuite\Filament\Pages\NotFoundUrlsPage;
use Capell\SeoSuite\Filament\Pages\SeoAuditPage;
use Capell\SeoSuite\Filament\Pages\SeoSuiteSettingsPage;
use Capell\SeoSuite\Filament\Pages\SitemapPage;
use Capell\SeoSuite\Filament\Pages\TranslationCoveragePage;
use Capell\SeoSuite\Filament\Settings\AIOrchestratorSettingsSchema;
use Capell\SeoSuite\Filament\Settings\SeoSettingsSchema;
use Capell\SeoSuite\Filament\Settings\StructuredDataSettingsSchema;
use Capell\SeoSuite\Handlers\ClearCircuitBreakerHandler;
use Capell\SeoSuite\Http\Controllers\LlmsTxtController;
use Capell\SeoSuite\Listeners\LogAiGeneration;
use Capell\SeoSuite\Listeners\NotifyAiFailure;
use Capell\SeoSuite\Listeners\RecordBrokenLink;
use Capell\SeoSuite\Listeners\Sitemap\RegenerateSitemapsOnPageDeleted;
use Capell\SeoSuite\Listeners\Sitemap\RegenerateSitemapsOnPageSaved;
use Capell\SeoSuite\Listeners\Sitemap\RegenerateSitemapsOnSiteCreated;
use Capell\SeoSuite\Livewire\Page\Sitemap as SitemapLivewireComponent;
use Capell\SeoSuite\Livewire\Tools\SitemapTool;
use Capell\SeoSuite\Models\AiCreatorContext;
use Capell\SeoSuite\Models\AiCreatorSession;
use Capell\SeoSuite\Models\AIGenerationHistory;
use Capell\SeoSuite\Models\BrokenLink;
use Capell\SeoSuite\Policies\AiCreatorPolicy;
use Capell\SeoSuite\Settings\AIOrchestratorSettings;
use Capell\SeoSuite\Settings\SeoSuiteSettings;
use Capell\SeoSuite\Support\Admin\AiCreatorPageExtender;
use Capell\SeoSuite\Support\Admin\AiCreatorSiteExtender;
use Capell\SeoSuite\Support\Admin\PageContentEditorConfigurator;
use Capell\SeoSuite\Support\Admin\PageSeoAuditPageEditExtender;
use Capell\SeoSuite\Support\Admin\PageSeoAuditPageResourceWidgetExtender;
use Capell\SeoSuite\Support\Admin\PageTitleWithSlugInputExtender;
use Capell\SeoSuite\Support\AdminTools\SitemapAdminTool;
use Capell\SeoSuite\Support\AiFeatureRegistry;
use Capell\SeoSuite\Support\AiRateLimiter;
use Capell\SeoSuite\Support\AiResponseParser;
use Capell\SeoSuite\Support\AiTokenCounter;
use Capell\SeoSuite\Support\Cache\AIGenerationCache;
use Capell\SeoSuite\Support\Cache\RateLimitCache;
use Capell\SeoSuite\Support\ContentGraph\BrokenLinkContentGraphExtractor;
use Capell\SeoSuite\Support\ContentGraph\PageSeoSnapshotContentGraphExtractor;
use Capell\SeoSuite\Support\ContentTargetResolver;
use Capell\SeoSuite\Support\Creator\SitemapPageCreator;
use Capell\SeoSuite\Support\Interceptors\SitemapPageTypeInterceptor;
use Capell\SeoSuite\Support\Pipelines\AiCreatorPipeline;
use Capell\SeoSuite\Support\PrismProvider;
use Capell\SeoSuite\Support\PromptRepository;
use Capell\SeoSuite\Support\Publishing\SeoPublishReportProviderAdapter;
use Capell\SeoSuite\Support\RenderHooks\RegisterSeoHeadHooks;
use Capell\SeoSuite\Support\Schemas\SearchMetaDataSectionExtenderResolver;
use Capell\SeoSuite\Support\SchemaTemplates\ArticleSchemaTemplate;
use Capell\SeoSuite\Support\SchemaTemplates\SchemaTemplateRegistry;
use Capell\SeoSuite\Support\SchemaTemplates\WebPageSchemaTemplate;
use Capell\SeoSuite\Support\SearchConsole\GoogleSearchConsoleClient;
use Capell\SeoSuite\Support\SearchConsole\NullSearchConsoleClient;
use Capell\SeoSuite\Support\SectionRegistry;
use Capell\SeoSuite\Support\Sitemap\Pages\PagesSitemap;
use Capell\SeoSuite\Support\Sitemap\SitemapPageRegistry;
use Capell\SeoSuite\Support\Sitemap\SitemapPageType;
use Capell\SeoSuite\Targets\FlatJsonTarget;
use Composer\InstalledVersions;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;

class SeoSuiteServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-seo-suite';

    public static string $packageName = 'capell-app/seo-suite';

    public static PackageTypeEnum $type = PackageTypeEnum::Plugin;

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasViews(self::$name)
            ->hasTranslations()
            ->hasConfigFile(self::$name)
            ->hasCommands([
                ClearAiCacheCommand::class,
                InstallCommand::class,
                MonitorAiUsageCommand::class,
                SetupCommand::class,
                TestOpenAiConnectionCommand::class,
                XmlSitemapCommand::class,
            ]);
    }

    public function registeringPackage(): void
    {
        $this->registerPackageMetadata();
        $this->registerContentGraphExtractors();

        $this->app->booted(function (): void {
            if (! $this->isPackageInstalled()) {
                return;
            }

            $this->bootInstalledPackage();
        });
    }

    /**
     * Discover migrations in database/migrations as filenames (no extension).
     *
     * @return array<int, string>
     */
    protected function discoveredMigrations(): array
    {
        return $this->discoverMigrations();
    }

    protected function registerAiServices(): self
    {
        $this->app->singleton(PrismProvider::class, fn (Application $app): PrismProvider => new PrismProvider(config('capell-seo-suite.prism', [])));

        $this->app->singleton(PromptRepository::class, fn (Application $app): PromptRepository => new PromptRepository(config('capell-seo-suite.prompts', [])));

        $this->app->singleton(AiResponseParser::class, fn (): AiResponseParser => new AiResponseParser);

        $this->app->singleton(AiRateLimiter::class, fn (Application $app): AiRateLimiter => new AiRateLimiter(
            $app->make(RateLimitCache::class),
            config('capell-seo-suite.rate_limiting', ['enabled' => false, 'requests_per_minute' => 60]),
        ));

        $this->app->singleton(AiTokenCounter::class, fn (): AiTokenCounter => new AiTokenCounter);

        $this->app->singleton(AiFeatureRegistry::class, fn (Application $app): AiFeatureRegistry => new AiFeatureRegistry(config('capell-seo-suite.features', [])));

        $this->app->singleton(AIGenerationCache::class, fn (Application $app): AIGenerationCache => new AIGenerationCache(
            config('cache.default'),
            config('capell-seo-suite.cache.ttl', 86400),
        ));

        $this->app->singleton(RateLimitCache::class, fn (\Illuminate\Foundation\Application $app): RateLimitCache => new RateLimitCache((string) config('cache.default')));

        $this->app->singleton(SectionRegistry::class, fn (): SectionRegistry => new SectionRegistry);

        $this->app->singleton(ContentTargetResolver::class, function (Application $app): ContentTargetResolver {
            $resolver = new ContentTargetResolver;
            $resolver->register($app->make(FlatJsonTarget::class));

            foreach ($app->tagged('capell-seo-suite:content-targets') as $target) {
                $resolver->register($target);
            }

            return $resolver;
        });

        $this->app->singleton(AiCreatorPolicy::class, fn (Application $app): AiCreatorPolicy => new AiCreatorPolicy(
            $app->make(AIOrchestratorSettings::class),
        ));

        $this->app->singleton(AiCreatorPipeline::class, fn (Application $app): AiCreatorPipeline => new AiCreatorPipeline(
            $app->make(PromptRepository::class),
            $app->make(PrismProvider::class),
            $app->make(AiRateLimiter::class),
            $app->make(SectionRegistry::class),
            $app->make(RecordAiGenerationAction::class),
        ));

        /** @var AiFeatureRegistry $registry */
        $registry = $this->app->make(AiFeatureRegistry::class);
        foreach (config('capell-seo-suite.features', []) as $name => $feature) {
            if (is_array($feature)) {
                $registry->register($name, $feature);
            }
        }

        return $this;
    }

    protected function registerAiEventListeners(): self
    {
        $events = $this->app->make(Dispatcher::class);
        $events->listen(
            AiGenerationFailed::class,
            NotifyAiFailure::class,
        );
        $events->listen(
            AiGenerationCompleted::class,
            LogAiGeneration::class,
        );

        return $this;
    }

    protected function registerBrokenLinkEventListeners(): self
    {
        $events = $this->app->make(Dispatcher::class);
        $events->listen(UrlVisitFailed::class, RecordBrokenLink::class);

        return $this;
    }

    protected function registerAdminEvents(): self
    {
        /** @var AdminEventRegistry $registry */
        $registry = $this->app->make(AdminEventRegistry::class);

        $registry->register(EditPage::class, 'clear-circuit-breaker', ClearCircuitBreakerHandler::class);

        return $this;
    }

    protected function registerAdminExtenders(): self
    {
        $this->app->tag([
            PageContentEditorConfigurator::class,
        ], 'capell-admin:page-content-editor');

        $this->app->tag([
            PageTitleWithSlugInputExtender::class,
        ], 'capell-admin:page-title-with-slug-input');

        $this->app->tag([
            AiCreatorPageExtender::class,
        ], PageHeaderActionExtender::TAG);

        $this->app->tag([
            AiCreatorSiteExtender::class,
            SitemapSiteHeaderActionExtender::class,
        ], SiteHeaderActionExtender::TAG);

        $this->app->tag([
            SitemapResourceHeaderActionExtender::class,
        ], ResourceHeaderActionExtender::TAG);

        $this->app->tag([
            SitemapSiteRecordActionExtender::class,
        ], SiteRecordActionExtender::TAG);

        $this->app->tag([
            SitemapAdminTool::class,
        ], AdminToolItem::TAG);

        $this->app->tag([
            PageSeoAuditPageEditExtender::class,
        ], PageEditExtender::TAG);

        $this->app->tag([
            PageSeoAuditPageResourceWidgetExtender::class,
        ], PageResourceWidgetExtender::TAG);

        return $this;
    }

    protected function registerPageSchemaExtenders(): self
    {
        $this->app->tag(
            [
                SearchMetaSchemaExtender::class,
                PageSeoSettingsTabExtender::class,
                PageSeoPanelSchemaExtender::class,
            ],
            PageSchemaExtender::TAG,
        );

        return $this;
    }

    protected function registerSiteSchemaExtenders(): self
    {
        $this->app->tag(
            [
                SiteTranslationMetaExtender::class,
                SiteDetailsMetaExtender::class,
            ],
            SiteSchemaExtender::TAG,
        );

        return $this;
    }

    protected function registerSettingsSchema(): self
    {
        /** @var SettingsSchemaRegistry $registry */
        $registry = $this->app->make(SettingsSchemaRegistry::class);
        $registry->register('ai-orchestrator', AIOrchestratorSettingsSchema::class);
        $registry->registerSettingsClass('ai-orchestrator', AIOrchestratorSettings::class);
        $registry->registerSettingsClass('seo_suite', SeoSuiteSettings::class);
        $registry->registerMetadata(new SettingsGroupMetadata(
            group: 'seo_suite',
            label: 'capell-seo-suite::generic.seo_settings',
            icon: Heroicon::OutlinedMagnifyingGlass,
            navigationGroup: 'capell-admin::navigation.group_extensions',
            navigationSort: 94,
            packageName: static::$packageName,
        ));
        $registry->register('seo_suite', SeoSettingsSchema::class);
        $registry->register('frontend', StructuredDataSettingsSchema::class);

        return $this;
    }

    protected function registerFilamentPages(): self
    {
        /** @var CapellAdminManager $adminManager */
        $adminManager = $this->app->make(CapellAdminManager::class);

        $adminManager->registerExtensionPage(static::$packageName, NotFoundUrlsPage::class);
        $adminManager->registerExtensionPage(static::$packageName, BrokenLinksPage::class);
        $adminManager->registerExtensionPage(static::$packageName, SeoAuditPage::class);
        $adminManager->registerExtensionPage(static::$packageName, SeoSuiteSettingsPage::class);
        $adminManager->registerExtensionPage(static::$packageName, TranslationCoveragePage::class);
        $adminManager->registerExtensionPage(static::$packageName, SitemapPage::class);

        return $this;
    }

    protected function registerFrontendViews(): self
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'capell');

        return $this;
    }

    protected function registerBlazeComponents(): self
    {
        RegisterBlazeOptimizedViewsAction::run(__DIR__ . '/../../resources/views/components/schema');

        return $this;
    }

    protected function registerLivewireComponents(): self
    {
        Livewire::component(SitemapPageType::ComponentView, SitemapLivewireComponent::class);
        Livewire::component('capell-seo-suite.tools.sitemap-tool', SitemapTool::class);

        return $this;
    }

    protected function registerSitemapPageType(): self
    {
        /** @var class-string<Type> $typeModel */
        $typeModel = Type::class;

        CapellCore::registerModelInterceptor(
            $typeModel,
            interceptorClass: SitemapPageTypeInterceptor::class,
            key: [
                'key' => SitemapPageType::Key,
                'type' => TypeEnum::Page,
            ],
        );

        return $this;
    }

    protected function registerSitemapDefaultPage(): self
    {
        CapellCore::addDefaultPage(
            'sitemap',
            __('capell::generic.sitemap'),
            function (Site $site, ?Collection $languages = null): void {
                resolve(SitemapPageCreator::class)->createSitemapPage($site, $languages);
            },
        );

        return $this;
    }

    protected function registerSitemapRegistry(): self
    {
        $this->app->singleton(SitemapPageRegistry::class);

        /** @var SitemapPageRegistry $registry */
        $registry = $this->app->make(SitemapPageRegistry::class);
        $registry->register('default', PagesSitemap::class);

        return $this;
    }

    protected function registerSchemaTemplateRegistry(): self
    {
        /** @var SchemaTemplateRegistry $registry */
        $registry = $this->app->make(SchemaTemplateRegistry::class);

        $registry->registerIfMissing(SchemaTemplateTypeEnum::WebPage, new WebPageSchemaTemplate);
        $registry->registerIfMissing(SchemaTemplateTypeEnum::Article, new ArticleSchemaTemplate);

        return $this;
    }

    protected function registerSitemapEventListeners(): self
    {
        $events = $this->app->make(Dispatcher::class);
        $events->listen(PageSaved::class, RegenerateSitemapsOnPageSaved::class);
        $events->listen(PageDeleted::class, RegenerateSitemapsOnPageDeleted::class);
        $events->listen(SiteCreated::class, RegenerateSitemapsOnSiteCreated::class);

        return $this;
    }

    protected function registerRenderHooks(): self
    {
        if (class_exists(RenderHookRegistry::class)) {
            $this->app->make(RegisterSeoHeadHooks::class)->register();
        }

        return $this;
    }

    protected function registerContentGraphExtractors(): self
    {
        if (class_exists(ContentGraphRegistry::class)) {
            $this->app->singleton(PageSeoSnapshotContentGraphExtractor::class);
            $this->app->singleton(BrokenLinkContentGraphExtractor::class);
            $this->app->tag([
                PageSeoSnapshotContentGraphExtractor::class,
                BrokenLinkContentGraphExtractor::class,
            ], ContentGraphRegistry::TAG);
        }

        return $this;
    }

    protected function registerLlmsTxtRoute(): self
    {
        Route::name('capell-frontend.')
            ->middleware(['web', 'frontend.resolve'])
            ->group(function (): void {
                Route::get('llms.txt', LlmsTxtController::class)->name('llms-txt');
            });

        return $this;
    }

    private function bootInstalledPackage(): self
    {
        return $this
            ->registerExtenderResolvers()
            ->registerModels()
            ->registerBlazeComponents()
            ->bindSchemaTemplateRegistry()
            ->bindSearchConsoleClient()
            ->bindSeoPublishReportProvider()
            ->registerAdminEvents()
            ->registerAdminExtenders()
            ->registerPageSchemaExtenders()
            ->registerSiteSchemaExtenders()
            ->registerAiServices()
            ->registerAiEventListeners()
            ->registerBrokenLinkEventListeners()
            ->registerSettingsSchema()
            ->registerSitemapPageType()
            ->registerSitemapDefaultPage()
            ->registerSitemapRegistry()
            ->registerSchemaTemplateRegistry()
            ->registerSitemapEventListeners()
            ->registerFilamentPages()
            ->registerLivewireComponents()
            ->registerFrontendViews()
            ->registerRenderHooks()
            ->registerLlmsTxtRoute();
    }

    private function isPackageInstalled(): bool
    {
        $package = CapellCore::getPackage(static::$packageName);

        return $package instanceof PackageData && $package->isInstalled();
    }

    private function registerExtenderResolvers(): self
    {
        $this->app->singleton(
            SearchMetaDataSectionExtenderResolverInterface::class,
            fn (): SearchMetaDataSectionExtenderResolver => new SearchMetaDataSectionExtenderResolver,
        );

        return $this;
    }

    private function bindSchemaTemplateRegistry(): self
    {
        $this->app->singleton(SchemaTemplateRegistry::class, fn (): SchemaTemplateRegistry => new SchemaTemplateRegistry);

        return $this;
    }

    private function bindSearchConsoleClient(): self
    {
        $this->app->singleton(SearchConsoleClientInterface::class, function (): SearchConsoleClientInterface {
            $config = config('capell-seo-suite.search_console', []);

            if (! is_array($config)) {
                return new NullSearchConsoleClient;
            }

            $credentialsPath = $config['credentials_path'] ?? null;

            if (($config['enabled'] ?? false) !== true || ! is_string($credentialsPath) || trim($credentialsPath) === '') {
                return new NullSearchConsoleClient;
            }

            return new GoogleSearchConsoleClient($config);
        });

        return $this;
    }

    private function bindSeoPublishReportProvider(): self
    {
        $this->app->singleton(SeoPublishReportProvider::class, SeoPublishReportProviderAdapter::class);

        return $this;
    }

    /**
     * @return array<int, string>
     */
    private function discoverMigrations(): array
    {
        $directory = realpath(__DIR__ . '/../../database/migrations');

        if ($directory === false) {
            return [];
        }

        $files = glob($directory . '/*.php') !== false ? glob($directory . '/*.php') : [];

        return array_map(
            static fn (string $path): string => pathinfo($path, PATHINFO_FILENAME),
            $files,
        );
    }

    private function registerPackageMetadata(): void
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            version: $this->getVersion(),
            setting: AIOrchestratorSettings::class,
            permissions: [],
            description: fn (): string => __('capell-seo-suite::package.description'),
        );
    }

    private function getVersion(): string
    {
        if (! class_exists(InstalledVersions::class)) {
            return 'dev';
        }

        if (! InstalledVersions::isInstalled(static::$packageName)) {
            return 'dev';
        }

        return InstalledVersions::getPrettyVersion(static::$packageName) ?? 'dev';
    }

    private function registerModels(): self
    {
        CapellCore::registerModels([
            AIGenerationHistory::class,
            AiCreatorContext::class,
            AiCreatorSession::class,
            BrokenLink::class,
        ]);

        return $this;
    }
}
