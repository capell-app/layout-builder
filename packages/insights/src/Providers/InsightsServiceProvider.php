<?php

declare(strict_types=1);

namespace Capell\Insights\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\Insights\Filament\Settings\InsightsSettingsSchema;
use Capell\Insights\Models\InsightsConsent;
use Capell\Insights\Models\InsightsEvent;
use Capell\Insights\Models\InsightsVisit;
use Capell\Insights\Settings\InsightsSettings;
use Capell\Insights\Settings\InsightsSettingsMigrationProvider;
use Capell\Insights\Support\RenderHooks\RegisterInsightsTrackerHook;
use Spatie\LaravelPackageTools\Package;

class InsightsServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-insights';

    public static string $packageName = 'capell-app/insights';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('capell-insights')
            ->hasTranslations()
            ->hasViews(self::$name)
            ->hasRoute('web')
            ->hasMigrations([
                'create_insights_visits_table',
                'create_insights_consents_table',
                'create_insights_events_table',
                'add_insights_reporting_indexes',
                'import_legacy_page_views',
                'add_page_url_hit_columns',
            ]);
    }

    public function registeringPackage(): void
    {
        $this->app->register(AdminServiceProvider::class);
    }

    public function packageRegistered(): void
    {
        $this
            ->registerPackageMetadata()
            ->registerSettingsMigrations();

        $this->app->booted(function (): void {
            if (! $this->isPackageInstalled()) {
                return;
            }

            $this
                ->registerModels()
                ->registerSettings()
                ->registerProtectedTables();
        });
    }

    public function packageBooted(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        if (config('capell-insights.enabled', true) === true && $this->app->bound(RenderHookRegistry::class)) {
            $this->app->make(RegisterInsightsTrackerHook::class)->register();
        }

        if (! $this->app->runningInConsole()) {
            return;
        }

        /** @var InsightsSettingsMigrationProvider $provider */
        $provider = $this->app->make(InsightsSettingsMigrationProvider::class);

        $this->publishes([
            $provider->path() . '/create_insights_settings.php' => database_path('settings/create_insights_settings.php'),
        ], 'capell-insights-settings');
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/../..'),
            version: CapellCore::getInstalledPrettyVersion(self::$packageName),
            description: fn (): string => __('capell-insights::package.description'),
        );

        return $this;
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(static::$packageName);
    }

    private function registerModels(): self
    {
        CapellCore::registerModels([
            InsightsVisit::class,
            InsightsConsent::class,
            InsightsEvent::class,
        ]);

        return $this;
    }

    private function registerSettings(): self
    {
        /** @var SettingsSchemaRegistry $registry */
        $registry = $this->app->make(SettingsSchemaRegistry::class);

        $registry->registerSettingsClass('insights', InsightsSettings::class);
        $registry->register('insights', InsightsSettingsSchema::class);

        return $this;
    }

    private function registerSettingsMigrations(): self
    {
        $this->app->singleton(InsightsSettingsMigrationProvider::class);

        return $this;
    }

    private function registerProtectedTables(): self
    {
        CapellCore::registerProtectedTable(fn (): string => config('capell-insights.tables.visits', 'insights_visits'));
        CapellCore::registerProtectedTable(fn (): string => config('capell-insights.tables.consents', 'insights_consents'));
        CapellCore::registerProtectedTable(fn (): string => config('capell-insights.tables.events', 'insights_events'));

        return $this;
    }
}
