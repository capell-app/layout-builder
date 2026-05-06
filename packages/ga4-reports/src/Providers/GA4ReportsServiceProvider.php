<?php

declare(strict_types=1);

namespace Capell\GA4Reports\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\GA4Reports\Actions\ResolveGA4ReportsConfigAction;
use Capell\GA4Reports\Contracts\GA4ReportsDataClientInterface;
use Capell\GA4Reports\Filament\Settings\GA4ReportsSettingsSchema;
use Capell\GA4Reports\Models\GA4ReportsDailyMetric;
use Capell\GA4Reports\Models\GA4ReportsPageMetric;
use Capell\GA4Reports\Models\GA4ReportsSyncRun;
use Capell\GA4Reports\Settings\GA4ReportsSettings;
use Capell\GA4Reports\Settings\GA4ReportsSettingsMigrationProvider;
use Capell\GA4Reports\Support\Insights\GA4ReportsDataClient;
use Capell\GA4Reports\Support\Insights\NullGA4ReportsDataClient;
use Spatie\LaravelPackageTools\Package;

final class GA4ReportsServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-ga4-reports';

    public static string $packageName = 'capell-app/ga4-reports';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('capell-ga4-reports')
            ->hasTranslations()
            ->hasViews(self::$name)
            ->hasMigrations([
                'create_ga4_reports_sync_runs_table',
                'create_ga4_reports_daily_metrics_table',
                'create_ga4_reports_page_metrics_table',
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
            ->bindGA4ReportsClient();

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

        /** @var GA4ReportsSettingsMigrationProvider $provider */
        $provider = $this->app->make(GA4ReportsSettingsMigrationProvider::class);

        $this->publishes([
            $provider->path() . '/create_ga4_reports_settings.php' => database_path('settings/create_ga4_reports_settings.php'),
        ], 'capell-ga4-reports-settings');
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/../..'),
            version: CapellCore::getInstalledPrettyVersion(self::$packageName),
            description: fn (): string => __('capell-ga4-reports::package.description'),
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
            GA4ReportsSyncRun::class,
            GA4ReportsDailyMetric::class,
            GA4ReportsPageMetric::class,
        ]);

        return $this;
    }

    private function registerSettings(): self
    {
        /** @var SettingsSchemaRegistry $registry */
        $registry = $this->app->make(SettingsSchemaRegistry::class);

        $registry->registerSettingsClass('ga4_reports', GA4ReportsSettings::class);
        $registry->register('ga4_reports', GA4ReportsSettingsSchema::class);

        return $this;
    }

    private function registerSettingsMigrations(): self
    {
        $this->app->singleton(GA4ReportsSettingsMigrationProvider::class);

        return $this;
    }

    private function bindGA4ReportsClient(): self
    {
        $this->app->singleton(GA4ReportsDataClientInterface::class, function (): GA4ReportsDataClientInterface {
            $resolvedConfig = ResolveGA4ReportsConfigAction::run();
            $config = [
                'enabled' => $resolvedConfig->enabled,
                'property_id' => $resolvedConfig->propertyId,
                'credentials_path' => $resolvedConfig->credentialsPath,
            ];

            if (! is_array($config)) {
                return new NullGA4ReportsDataClient;
            }

            return new GA4ReportsDataClient($config);
        });

        return $this;
    }

    private function registerProtectedTables(): self
    {
        CapellCore::registerProtectedTable(fn (): string => config('capell-ga4-reports.tables.sync_runs', 'ga4_reports_sync_runs'));
        CapellCore::registerProtectedTable(fn (): string => config('capell-ga4-reports.tables.daily_metrics', 'ga4_reports_daily_metrics'));
        CapellCore::registerProtectedTable(fn (): string => config('capell-ga4-reports.tables.page_metrics', 'ga4_reports_page_metrics'));

        return $this;
    }
}
