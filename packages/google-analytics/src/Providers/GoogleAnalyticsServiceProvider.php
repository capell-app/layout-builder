<?php

declare(strict_types=1);

namespace Capell\GoogleAnalytics\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\GoogleAnalytics\Actions\ResolveGoogleAnalyticsConfigAction;
use Capell\GoogleAnalytics\Contracts\GoogleAnalyticsDataClientInterface;
use Capell\GoogleAnalytics\Filament\Settings\GoogleAnalyticsSettingsSchema;
use Capell\GoogleAnalytics\Models\GoogleAnalyticsDailyMetric;
use Capell\GoogleAnalytics\Models\GoogleAnalyticsPageMetric;
use Capell\GoogleAnalytics\Models\GoogleAnalyticsSyncRun;
use Capell\GoogleAnalytics\Settings\GoogleAnalyticsSettings;
use Capell\GoogleAnalytics\Settings\GoogleAnalyticsSettingsMigrationProvider;
use Capell\GoogleAnalytics\Support\Analytics\GoogleAnalyticsDataClient;
use Capell\GoogleAnalytics\Support\Analytics\NullGoogleAnalyticsDataClient;
use Spatie\LaravelPackageTools\Package;

final class GoogleAnalyticsServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-google-analytics';

    public static string $packageName = 'capell-app/google-analytics';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('capell-google-analytics')
            ->hasTranslations()
            ->hasViews(self::$name)
            ->hasMigrations([
                'create_google_analytics_sync_runs_table',
                'create_google_analytics_daily_metrics_table',
                'create_google_analytics_page_metrics_table',
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
            ->registerSettingsMigrations()
            ->bindGoogleAnalyticsClient();

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
        if (! $this->isPackageInstalled() || ! $this->app->runningInConsole()) {
            return;
        }

        /** @var GoogleAnalyticsSettingsMigrationProvider $provider */
        $provider = $this->app->make(GoogleAnalyticsSettingsMigrationProvider::class);

        $this->publishes([
            $provider->path() . '/create_google_analytics_settings.php' => database_path('settings/create_google_analytics_settings.php'),
        ], 'capell-google-analytics-settings');
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/../..'),
            version: CapellCore::getInstalledPrettyVersion(self::$packageName),
            description: fn (): string => __('capell-google-analytics::package.description'),
        );

        return $this;
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(self::$packageName);
    }

    private function registerModels(): self
    {
        CapellCore::registerModels([
            GoogleAnalyticsSyncRun::class,
            GoogleAnalyticsDailyMetric::class,
            GoogleAnalyticsPageMetric::class,
        ]);

        return $this;
    }

    private function registerSettings(): self
    {
        /** @var SettingsSchemaRegistry $registry */
        $registry = $this->app->make(SettingsSchemaRegistry::class);

        $registry->registerSettingsClass('google_analytics', GoogleAnalyticsSettings::class);
        $registry->register('google_analytics', GoogleAnalyticsSettingsSchema::class);

        return $this;
    }

    private function registerSettingsMigrations(): self
    {
        $this->app->singleton(GoogleAnalyticsSettingsMigrationProvider::class);

        return $this;
    }

    private function bindGoogleAnalyticsClient(): self
    {
        $this->app->singleton(GoogleAnalyticsDataClientInterface::class, function (): GoogleAnalyticsDataClientInterface {
            $resolvedConfig = ResolveGoogleAnalyticsConfigAction::run();
            $config = [
                'enabled' => $resolvedConfig->enabled,
                'property_id' => $resolvedConfig->propertyId,
                'credentials_path' => $resolvedConfig->credentialsPath,
            ];

            if (! is_array($config)) {
                return new NullGoogleAnalyticsDataClient;
            }

            return new GoogleAnalyticsDataClient($config);
        });

        return $this;
    }

    private function registerProtectedTables(): self
    {
        CapellCore::registerProtectedTable(fn (): string => config('capell-google-analytics.tables.sync_runs', 'google_analytics_sync_runs'));
        CapellCore::registerProtectedTable(fn (): string => config('capell-google-analytics.tables.daily_metrics', 'google_analytics_daily_metrics'));
        CapellCore::registerProtectedTable(fn (): string => config('capell-google-analytics.tables.page_metrics', 'google_analytics_page_metrics'));

        return $this;
    }
}
