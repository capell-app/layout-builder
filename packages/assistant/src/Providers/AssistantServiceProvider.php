<?php

declare(strict_types=1);

namespace Capell\Assistant\Providers;

use Capell\Admin\Contracts\Extenders\PageTitleWithSlugInputExtender as PageTitleWithSlugInputExtenderContract;
use Capell\Admin\Contracts\Extenders\SearchMetaDataSectionExtender as SearchMetaDataSectionExtenderContract;
use Capell\Admin\Filament\Resources\Pages\Pages\EditPage;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Support\AdminEventRegistry;
use Capell\Assistant\Console\Commands\ClearAiCacheCommand;
use Capell\Assistant\Console\Commands\InstallCommand;
use Capell\Assistant\Console\Commands\MonitorAiUsageCommand;
use Capell\Assistant\Console\Commands\TestOpenAiConnectionCommand;
use Capell\Assistant\Data\PromptData;
use Capell\Assistant\Events\AiGenerationCompleted;
use Capell\Assistant\Events\AiGenerationFailed;
use Capell\Assistant\Filament\Settings\AssistantSettingsSchema;
use Capell\Assistant\Handlers\ClearCircuitBreakerHandler;
use Capell\Assistant\Listeners\LogAiGeneration;
use Capell\Assistant\Listeners\NotifyAiFailure;
use Capell\Assistant\Models\AIGenerationHistory;
use Capell\Assistant\Settings\AssistantSettings;
use Capell\Assistant\Support\Admin\PageContentEditorConfigurator;
use Capell\Assistant\Support\Admin\PageTitleWithSlugInputExtender;
use Capell\Assistant\Support\Admin\SearchMetaDataSectionExtender;
use Capell\Assistant\Support\AiFeatureRegistry;
use Capell\Assistant\Support\AiRateLimiter;
use Capell\Assistant\Support\AiResponseParser;
use Capell\Assistant\Support\AiTokenCounter;
use Capell\Assistant\Support\Cache\AIGenerationCache;
use Capell\Assistant\Support\Cache\RateLimitCache;
use Capell\Assistant\Support\OpenAIProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Composer\InstalledVersions;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Spatie\LaravelPackageTools\Package;

class AssistantServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-assistant';

    public static string $packageName = 'capell-app/assistant';

    public static string $description = 'AI Assistant for Capell';

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name)
            ->hasViews(self::$name)
            ->hasConfigFile(self::$name)
            ->hasTranslations()
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

    protected function registerOpenAiCmsIntegrationServices(): self
    {
        $this->app->singleton(
            OpenAIProvider::class,
            fn (Application $app): OpenAIProvider => new OpenAIProvider(config('capell-assistant.openai', [])),
        );

        $this->app->singleton(
            PromptData::class,
            fn (Application $application): PromptData => $this->resolvePromptData($application->make(AssistantSettings::class)->prompts),
        );

        $this->app->singleton(AiResponseParser::class, fn (): AiResponseParser => new AiResponseParser);

        $this->app->singleton(AiRateLimiter::class, fn (Application $app): AiRateLimiter => new AiRateLimiter(
            $app->make(RateLimitCache::class),
            config('capell-assistant.rate_limiting', ['enabled' => false, 'requests_per_minute' => resolve(AssistantSettings::class)->prompts['rate_limiting_requests_per_minute'] ?? 60]),
        ));

        $this->app->singleton(AiTokenCounter::class, fn (): AiTokenCounter => new AiTokenCounter);

        $this->app->singleton(
            AiFeatureRegistry::class,
            fn (Application $app): AiFeatureRegistry => new AiFeatureRegistry(config('capell-assistant.features', [])),
        );

        $this->app->singleton(AIGenerationCache::class, fn (Application $app): AIGenerationCache => new AIGenerationCache(
            (string) config('cache.default'),
            config('capell-assistant.cache.ttl', 86400),
        ));

        $this->app->singleton(RateLimitCache::class, fn (\Illuminate\Foundation\Application $app): RateLimitCache => new RateLimitCache((string) config('cache.default')));

        /** @var AiFeatureRegistry $registry */
        $registry = $this->app->make(AiFeatureRegistry::class);
        $features = config('capell-assistant.features', []);
        foreach ($features as $name => $feature) {
            if (is_array($feature)) {
                $registry->register($name, $feature);
            }
        }

        return $this;
    }

    protected function registerOpenAiCmsIntegrationEventListeners(): self
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
        ], PageTitleWithSlugInputExtenderContract::TAG);

        $this->app->tag([
            SearchMetaDataSectionExtender::class,
        ], SearchMetaDataSectionExtenderContract::TAG);

        return $this;
    }

    private function bootInstalledPackage(): self
    {
        return $this
            ->registerAdminEvents()
            ->registerAdminExtenders()
            ->registerOpenAiCmsIntegrationServices()
            ->registerOpenAiCmsIntegrationEventListeners()
            ->registerSettingsSchemas();
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
            path: realpath(__DIR__ . '/../..'),
            icon: 'heroicon-o-rocket-launch',
            description: static::getDescription(),
            installCommand: 'capell:assistant-install',
            setting: AssistantSettings::class,
            requirements: [
                AdminServiceProvider::$packageName,
            ],
            version: $this->getVersion(),
            url: 'https://capell.app',
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

        return $this;
    }

    /**
     * @param  array<string, bool|int|string|null>  $prompts
     */
    private function resolvePromptData(array $prompts): PromptData
    {
        return new PromptData(
            model: $this->resolveNullableString($prompts['model'] ?? null),
            titleGeneration: $this->resolveBoolean($prompts['title_generation'] ?? false),
            titleGenerationSystem: $this->resolveNullableString($prompts['title_generation_system'] ?? null),
            titleGenerationUserTemplate: $this->resolveNullableString($prompts['title_generation_user_template'] ?? null),
            metaDescription: $this->resolveBoolean($prompts['meta_description'] ?? false),
            metaDescriptionSystem: $this->resolveNullableString($prompts['meta_description_system'] ?? null),
            metaDescriptionUserTemplate: $this->resolveNullableString($prompts['meta_description_user_template'] ?? null),
            contentGeneration: $this->resolveBoolean($prompts['content_generation'] ?? false),
            contentGenerationSystem: $this->resolveNullableString($prompts['content_generation_system'] ?? null),
            contentGenerationUserTemplate: $this->resolveNullableString($prompts['content_generation_user_template'] ?? null),
        );
    }

    private function resolveBoolean(bool|int|string|null $value): bool
    {
        return match (true) {
            is_bool($value) => $value,
            is_int($value) => $value === 1,
            is_string($value) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => false,
        };
    }

    private function resolveNullableString(bool|int|string|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    private function registerSettingsSchemas(): self
    {
        $registry = resolve(SettingsSchemaRegistry::class);

        $registry->registerSettingsClass('assistant', AssistantSettings::class);
        $registry->register('assistant', AssistantSettingsSchema::class);

        return $this;
    }
}
