<?php

declare(strict_types=1);

namespace Capell\SeoTools\Providers;

use Capell\Admin\Contracts\Extenders\PageHeaderActionExtender;
use Capell\Admin\Contracts\Extenders\SiteHeaderActionExtender;
use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Admin\Support\AdminEventRegistry;
use Capell\Admin\Support\CapellAdminManager;
use Capell\Core\Data\PackageData;
use Capell\Core\Enums\PackageTypeEnum;
use Capell\Core\Enums\TypeEnum;
use Capell\Core\Events\PageDeleted;
use Capell\Core\Events\PageSaved;
use Capell\Core\Events\SiteCreated;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\SeoTools\Console\Commands\ClearAiCacheCommand;
use Capell\SeoTools\Console\Commands\InstallCommand;
use Capell\SeoTools\Console\Commands\MonitorAiUsageCommand;
use Capell\SeoTools\Console\Commands\SetupCommand;
use Capell\SeoTools\Console\Commands\TestOpenAiConnectionCommand;
use Capell\SeoTools\Console\Commands\XmlSitemapCommand;
use Capell\SeoTools\Contracts\Schemas\SearchMetaDataSectionExtenderResolverInterface;
use Capell\SeoTools\Events\AiGenerationCompleted;
use Capell\SeoTools\Events\AiGenerationFailed;
use Capell\SeoTools\Filament\Pages\SitemapPage;
use Capell\SeoTools\Filament\Settings\AssistantSettingsSchema;
use Capell\SeoTools\Filament\Settings\SeoSettingsSchema;
use Capell\SeoTools\Handlers\ClearCircuitBreakerHandler;
use Capell\SeoTools\Listeners\LogAiGeneration;
use Capell\SeoTools\Listeners\NotifyAiFailure;
use Capell\SeoTools\Listeners\Sitemap\RegenerateSitemapsOnPageDeleted;
use Capell\SeoTools\Listeners\Sitemap\RegenerateSitemapsOnPageSaved;
use Capell\SeoTools\Listeners\Sitemap\RegenerateSitemapsOnSiteCreated;
use Capell\SeoTools\Livewire\Page\Sitemap as SitemapLivewireComponent;
use Capell\SeoTools\Models\AiCreatorContext;
use Capell\SeoTools\Models\AiCreatorSession;
use Capell\SeoTools\Models\AIGenerationHistory;
use Capell\SeoTools\Policies\AiCreatorPolicy;
use Capell\SeoTools\Settings\AssistantSettings;
use Capell\SeoTools\Support\Admin\AiCreatorPageExtender;
use Capell\SeoTools\Support\Admin\AiCreatorSiteExtender;
use Capell\SeoTools\Support\Admin\PageContentEditorConfigurator;
use Capell\SeoTools\Support\Admin\PageTitleWithSlugInputExtender;
use Capell\SeoTools\Support\AiFeatureRegistry;
use Capell\SeoTools\Support\AiRateLimiter;
use Capell\SeoTools\Support\AiResponseParser;
use Capell\SeoTools\Support\AiTokenCounter;
use Capell\SeoTools\Support\Cache\AIGenerationCache;
use Capell\SeoTools\Support\Cache\RateLimitCache;
use Capell\SeoTools\Support\ContentTargetResolver;
use Capell\SeoTools\Support\Creator\SitemapPageCreator;
use Capell\SeoTools\Support\Interceptors\SitemapPageTypeInterceptor;
use Capell\SeoTools\Support\Pipelines\AiCreatorPipeline;
use Capell\SeoTools\Support\PrismProvider;
use Capell\SeoTools\Support\PromptRepository;
use Capell\SeoTools\Support\Schemas\SearchMetaDataSectionExtenderResolver;
use Capell\SeoTools\Support\SectionRegistry;
use Capell\SeoTools\Support\Sitemap\Pages\PagesSitemap;
use Capell\SeoTools\Support\Sitemap\SitemapPageRegistry;
use Capell\SeoTools\Support\Sitemap\SitemapPageType;
use Capell\SeoTools\Targets\FlatJsonTarget;
use Composer\InstalledVersions;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;

class SeoToolsServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-seo-tools';

    public static string $packageName = 'capell-app/seo-tools';

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
        $this->registerExtenderResolvers();
        $this->registerModels();

        $this->booted(function (): void {
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
        $this->app->singleton(PrismProvider::class, fn (Application $app): PrismProvider => new PrismProvider(config('capell-seo-tools.prism', [])));

        $this->app->singleton(PromptRepository::class, fn (Application $app): PromptRepository => new PromptRepository(config('capell-seo-tools.prompts', [])));

        $this->app->singleton(AiResponseParser::class, fn (): AiResponseParser => new AiResponseParser);

        $this->app->singleton(AiRateLimiter::class, fn (Application $app): AiRateLimiter => new AiRateLimiter(
            $app->make(RateLimitCache::class),
            config('capell-seo-tools.rate_limiting', ['enabled' => false, 'requests_per_minute' => 60]),
        ));

        $this->app->singleton(AiTokenCounter::class, fn (): AiTokenCounter => new AiTokenCounter);

        $this->app->singleton(AiFeatureRegistry::class, fn (Application $app): AiFeatureRegistry => new AiFeatureRegistry(config('capell-seo-tools.features', [])));

        $this->app->singleton(AIGenerationCache::class, fn (Application $app): AIGenerationCache => new AIGenerationCache(
            config('cache.default'),
            config('capell-seo-tools.cache.ttl', 86400),
        ));

        $this->app->singleton(RateLimitCache::class, fn (\Illuminate\Foundation\Application $app): RateLimitCache => new RateLimitCache((string) config('cache.default')));

        $this->app->singleton(SectionRegistry::class, fn (): SectionRegistry => new SectionRegistry);

        $this->app->singleton(ContentTargetResolver::class, function (Application $app): ContentTargetResolver {
            $resolver = new ContentTargetResolver;
            $resolver->register($app->make(FlatJsonTarget::class));

            foreach ($app->tagged('capell-seo-tools:content-targets') as $target) {
                $resolver->register($target);
            }

            return $resolver;
        });

        $this->app->singleton(AiCreatorPolicy::class, fn (Application $app): AiCreatorPolicy => new AiCreatorPolicy(
            $app->make(AssistantSettings::class),
        ));

        $this->app->singleton(AiCreatorPipeline::class, fn (Application $app): AiCreatorPipeline => new AiCreatorPipeline(
            $app->make(PromptRepository::class),
            $app->make(PrismProvider::class),
            $app->make(AiRateLimiter::class),
            $app->make(SectionRegistry::class),
        ));

        /** @var AiFeatureRegistry $registry */
        $registry = $this->app->make(AiFeatureRegistry::class);
        foreach (config('capell-seo-tools.features', []) as $name => $feature) {
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
        ], SiteHeaderActionExtender::TAG);

        return $this;
    }

    protected function registerSettingsSchema(): self
    {
        /** @var SettingsSchemaRegistry $registry */
        $registry = $this->app->make(SettingsSchemaRegistry::class);
        $registry->register('assistant', AssistantSettingsSchema::class);
        $registry->registerSettingsClass('assistant', AssistantSettings::class);
        $registry->register('core', SeoSettingsSchema::class);

        return $this;
    }

    protected function registerFilamentPages(): self
    {
        /** @var CapellAdminManager $adminManager */
        $adminManager = $this->app->make(CapellAdminManager::class);
        $adminManager->registerPage(SitemapPage::class);

        return $this;
    }

    protected function registerFrontendViews(): self
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'capell');

        return $this;
    }

    protected function registerLivewireComponents(): self
    {
        Livewire::component('capell::page.sitemap', SitemapLivewireComponent::class);

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

    protected function registerSitemapEventListeners(): self
    {
        $events = $this->app->make(Dispatcher::class);
        $events->listen(PageSaved::class, RegenerateSitemapsOnPageSaved::class);
        $events->listen(PageDeleted::class, RegenerateSitemapsOnPageDeleted::class);
        $events->listen(SiteCreated::class, RegenerateSitemapsOnSiteCreated::class);

        return $this;
    }

    private function bootInstalledPackage(): self
    {
        return $this
            ->registerAdminEvents()
            ->registerAdminExtenders()
            ->registerAiServices()
            ->registerAiEventListeners()
            ->registerSettingsSchema()
            ->registerSitemapPageType()
            ->registerSitemapDefaultPage()
            ->registerSitemapRegistry()
            ->registerSitemapEventListeners()
            ->registerFilamentPages()
            ->registerLivewireComponents()
            ->registerFrontendViews();
    }

    private function isPackageInstalled(): bool
    {
        $package = CapellCore::getPackage(static::$packageName);

        return $package instanceof PackageData && $package->isInstalled();
    }

    private function registerExtenderResolvers(): void
    {
        $this->app->singleton(
            SearchMetaDataSectionExtenderResolverInterface::class,
            fn (): SearchMetaDataSectionExtenderResolver => new SearchMetaDataSectionExtenderResolver,
        );
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
            setting: AssistantSettings::class,
            permissions: [],
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

    private function registerModels(): void
    {
        CapellCore::registerModels([
            AIGenerationHistory::class,
            AiCreatorContext::class,
            AiCreatorSession::class,
        ]);
    }
}
