<?php

declare(strict_types=1);

namespace Capell\Analytics\Providers;

use Capell\Analytics\Filament\Settings\AnalyticsSettingsSchema;
use Capell\Analytics\Models\AnalyticsConsent;
use Capell\Analytics\Models\AnalyticsEvent;
use Capell\Analytics\Models\AnalyticsVisit;
use Capell\Analytics\Settings\AnalyticsSettings;
use Capell\Analytics\Settings\AnalyticsSettingsMigrationProvider;
use Capell\Analytics\Support\RenderHooks\RegisterAnalyticsTrackerHook;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Spatie\LaravelPackageTools\Package;

class AnalyticsServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-analytics';

    public static string $packageName = 'capell-app/analytics';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('capell-analytics')
            ->hasTranslations()
            ->hasViews(self::$name)
            ->hasRoute('web')
            ->hasMigrations([
                'create_analytics_visits_table',
                'create_analytics_consents_table',
                'create_analytics_events_table',
                'add_analytics_reporting_indexes',
                'import_legacy_page_views',
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

        if (config('capell-analytics.enabled', true) === true && $this->app->bound(RenderHookRegistry::class)) {
            $this->app->make(RegisterAnalyticsTrackerHook::class)->register();
        }

        if (! $this->app->runningInConsole()) {
            return;
        }

        /** @var AnalyticsSettingsMigrationProvider $provider */
        $provider = $this->app->make(AnalyticsSettingsMigrationProvider::class);

        $this->publishes([
            $provider->path() . '/create_analytics_settings.php' => database_path('settings/create_analytics_settings.php'),
        ], 'capell-analytics-settings');
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/../..'),
            version: CapellCore::getInstalledPrettyVersion(self::$packageName),
            description: fn (): string => __('capell-analytics::package.description'),
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
            AnalyticsVisit::class,
            AnalyticsConsent::class,
            AnalyticsEvent::class,
        ]);

        return $this;
    }

    private function registerSettings(): self
    {
        /** @var SettingsSchemaRegistry $registry */
        $registry = $this->app->make(SettingsSchemaRegistry::class);

        $registry->registerSettingsClass('analytics', AnalyticsSettings::class);
        $registry->register('analytics', AnalyticsSettingsSchema::class);

        return $this;
    }

    private function registerSettingsMigrations(): self
    {
        $this->app->singleton(AnalyticsSettingsMigrationProvider::class);

        return $this;
    }

    private function registerProtectedTables(): self
    {
        CapellCore::registerProtectedTable(fn (): string => config('capell-analytics.tables.visits', 'analytics_visits'));
        CapellCore::registerProtectedTable(fn (): string => config('capell-analytics.tables.consents', 'analytics_consents'));
        CapellCore::registerProtectedTable(fn (): string => config('capell-analytics.tables.events', 'analytics_events'));

        return $this;
    }
}
