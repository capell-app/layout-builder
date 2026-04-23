<?php

declare(strict_types=1);

namespace Capell\SeoTools\Assistant\Providers;

use Capell\Admin\Contracts\Extenders\PageHeaderActionExtender;
use Capell\Admin\Contracts\Extenders\SiteHeaderActionExtender;
use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Admin\Support\AdminEventRegistry;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\SeoTools\Assistant\Console\Commands\ClearAiCacheCommand;
use Capell\SeoTools\Assistant\Console\Commands\InstallCommand;
use Capell\SeoTools\Assistant\Console\Commands\MonitorAiUsageCommand;
use Capell\SeoTools\Assistant\Console\Commands\TestOpenAiConnectionCommand;
use Capell\SeoTools\Assistant\Events\AiGenerationCompleted;
use Capell\SeoTools\Assistant\Events\AiGenerationFailed;
use Capell\SeoTools\Assistant\Filament\Settings\AssistantSettingsSchema;
use Capell\SeoTools\Assistant\Handlers\ClearCircuitBreakerHandler;
use Capell\SeoTools\Assistant\Listeners\LogAiGeneration;
use Capell\SeoTools\Assistant\Listeners\NotifyAiFailure;
use Capell\SeoTools\Assistant\Models\AiCreatorContext;
use Capell\SeoTools\Assistant\Models\AiCreatorSession;
use Capell\SeoTools\Assistant\Models\AIGenerationHistory;
use Capell\SeoTools\Assistant\Policies\AiCreatorPolicy;
use Capell\SeoTools\Assistant\Settings\AssistantSettings;
use Capell\SeoTools\Assistant\Support\Admin\AiCreatorPageExtender;
use Capell\SeoTools\Assistant\Support\Admin\AiCreatorSiteExtender;
use Capell\SeoTools\Assistant\Support\Admin\PageContentEditorConfigurator;
use Capell\SeoTools\Assistant\Support\Admin\PageTitleWithSlugInputExtender;
use Capell\SeoTools\Assistant\Support\AiFeatureRegistry;
use Capell\SeoTools\Assistant\Support\AiRateLimiter;
use Capell\SeoTools\Assistant\Support\AiResponseParser;
use Capell\SeoTools\Assistant\Support\AiTokenCounter;
use Capell\SeoTools\Assistant\Support\Cache\AIGenerationCache;
use Capell\SeoTools\Assistant\Support\Cache\RateLimitCache;
use Capell\SeoTools\Assistant\Support\ContentTargetResolver;
use Capell\SeoTools\Assistant\Support\Pipelines\AiCreatorPipeline;
use Capell\SeoTools\Assistant\Support\PrismProvider;
use Capell\SeoTools\Assistant\Support\PromptRepository;
use Capell\SeoTools\Assistant\Support\SectionRegistry;
use Capell\SeoTools\Assistant\Targets\FlatJsonTarget;
use Composer\InstalledVersions;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Spatie\LaravelPackageTools\Package;

class AssistantServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-assistant';

    public static string $packageName = 'capell-app/assistant';

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name)
            ->hasViews(self::$name)
            ->hasTranslations()
            ->hasConfigFile(self::$name)
            ->hasCommands([
                ClearAiCacheCommand::class,
                InstallCommand::class,
                MonitorAiUsageCommand::class,
                TestOpenAiConnectionCommand::class,
            ]);
    }

    public function registeringPackage(): void
    {
        $this
            ->registerModels()
            ->registerPackageMetadata();

        $this->booted(function (): void {
            if (! $this->isPackageInstalled()) {
                return;
            }

            $this->bootInstalledPackage();
        });
    }

    protected function registerAiServices(): self
    {
        $this->app->singleton(PrismProvider::class, fn (Application $app): PrismProvider => new PrismProvider(config('capell-assistant.prism', [])));

        $this->app->singleton(PromptRepository::class, fn (Application $app): PromptRepository => new PromptRepository(config('capell-assistant.prompts', [])));

        $this->app->singleton(AiResponseParser::class, fn (): AiResponseParser => new AiResponseParser);

        $this->app->singleton(AiRateLimiter::class, fn (Application $app): AiRateLimiter => new AiRateLimiter(
            $app->make(RateLimitCache::class),
            config('capell-assistant.rate_limiting', ['enabled' => false, 'requests_per_minute' => 60]),
        ));

        $this->app->singleton(AiTokenCounter::class, fn (): AiTokenCounter => new AiTokenCounter);

        $this->app->singleton(AiFeatureRegistry::class, fn (Application $app): AiFeatureRegistry => new AiFeatureRegistry(config('capell-assistant.features', [])));

        $this->app->singleton(AIGenerationCache::class, fn (Application $app): AIGenerationCache => new AIGenerationCache(
            config('cache.default'),
            config('capell-assistant.cache.ttl', 86400),
        ));

        $this->app->singleton(RateLimitCache::class, fn (\Illuminate\Foundation\Application $app): RateLimitCache => new RateLimitCache((string) config('cache.default')));

        $this->app->singleton(SectionRegistry::class, fn (): SectionRegistry => new SectionRegistry);

        $this->app->singleton(ContentTargetResolver::class, function (Application $app): ContentTargetResolver {
            $resolver = new ContentTargetResolver;
            $resolver->register($app->make(FlatJsonTarget::class));

            foreach ($app->tagged('capell-assistant:content-targets') as $target) {
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
        foreach (config('capell-assistant.features', []) as $name => $feature) {
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
        // Keep provider clean: tag small extender/configurator classes used by Admin UI to augment components
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

        // Optional: register schema component replacers for DefaultPageSchema if needed in future
        // $this->app->tag([SomeReplacer::class], 'capell-admin:schema-component-replacers:page');

        return $this;
    }

    protected function registerSettingsSchema(): self
    {
        /** @var SettingsSchemaRegistry $registry */
        $registry = $this->app->make(SettingsSchemaRegistry::class);
        $registry->register('assistant', AssistantSettingsSchema::class);
        $registry->registerSettingsClass('assistant', AssistantSettings::class);

        return $this;
    }

    private function bootInstalledPackage(): self
    {
        return $this
            ->registerAdminEvents()
            ->registerAdminExtenders()
            ->registerAiServices()
            ->registerAiEventListeners()
            ->registerSettingsSchema();
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::getPackage(static::$packageName)->isInstalled();
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../../..'),
            version: $this->getVersion(),
            setting: AssistantSettings::class,
        );

        return $this;
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
        CapellCore::registerModel('AIGenerationHistory', AIGenerationHistory::class);
        CapellCore::registerModel('AiCreatorContext', AiCreatorContext::class);
        CapellCore::registerModel('AiCreatorSession', AiCreatorSession::class);

        return $this;
    }
}
