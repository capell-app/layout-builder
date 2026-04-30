<?php

declare(strict_types=1);

namespace Capell\Analytics\Providers;

use Capell\Analytics\Filament\Settings\AnalyticsSettingsSchema;
use Capell\Analytics\Models\AnalyticsConsent;
use Capell\Analytics\Models\AnalyticsEvent;
use Capell\Analytics\Models\AnalyticsVisit;
use Capell\Analytics\Settings\AnalyticsSettings;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
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
            ->registerModels()
            ->registerSettings()
            ->registerProtectedTables();
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

    private function registerProtectedTables(): self
    {
        CapellCore::registerProtectedTable(fn (): string => config('capell-analytics.tables.visits', 'analytics_visits'));
        CapellCore::registerProtectedTable(fn (): string => config('capell-analytics.tables.consents', 'analytics_consents'));
        CapellCore::registerProtectedTable(fn (): string => config('capell-analytics.tables.events', 'analytics_events'));

        return $this;
    }
}
