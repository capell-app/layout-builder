<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Providers;

use Capell\Admin\Contracts\Bridges\UserResourceBridge;
use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsGroupMetadata;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\WelcomeTour\Filament\Extenders\WelcomeTourPanelExtender;
use Capell\WelcomeTour\Filament\Pages\WelcomeTourDashboard;
use Capell\WelcomeTour\Filament\Settings\WelcomeTourSettingsSchema;
use Capell\WelcomeTour\Settings\WelcomeTourSettings;
use Capell\WelcomeTour\Support\WelcomeTourUserResourceBridge;
use Composer\InstalledVersions;
use Filament\Support\Icons\Heroicon;
use Spatie\LaravelPackageTools\Package;

class WelcomeTourServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-welcome-tour';

    public static string $packageName = 'capell-app/welcome-tour';

    public static function getSettingMigrations(): array
    {
        return [
            '2026_05_10_190836_01_add_welcome_tour_settings',
        ];
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile()
            ->hasTranslations();
    }

    public function registeringPackage(): void
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            version: $this->getVersion(),
            setting: WelcomeTourSettings::class,
            description: fn (): string => __('capell-welcome-tour::package.description'),
        );

        $this->booted(function (): void {
            if ($this->isDiscoveringPackages()) {
                return;
            }

            if (! $this->shouldRegisterRuntime()) {
                return;
            }

            $this->bootInstalledPackage();
        });
    }

    private function bootInstalledPackage(): void
    {
        $this->app->tag([WelcomeTourPanelExtender::class], AdminPanelExtender::TAG);
        $this->app->tag([WelcomeTourUserResourceBridge::class], UserResourceBridge::TAG);

        CapellAdmin::useDashboardPage(WelcomeTourDashboard::class);

        $this->registerSettings();
    }

    private function shouldRegisterRuntime(): bool
    {
        if (! config('capell-welcome-tour.enabled', true)) {
            return false;
        }

        return CapellCore::isPackageInstalled(static::$packageName);
    }

    private function registerSettings(): void
    {
        $registerSettings = function (SettingsSchemaRegistry $registry): void {
            $registry->registerSettingsClass(WelcomeTourSettings::group(), WelcomeTourSettings::class);
            $registry->registerMetadata(new SettingsGroupMetadata(
                group: WelcomeTourSettings::group(),
                label: 'capell-welcome-tour::welcome_tour.settings_label',
                icon: Heroicon::OutlinedSparkles,
                navigationGroup: 'capell-admin::navigation.group_system',
                navigationSort: 92,
                packageName: static::$packageName,
            ));
            $registry->register(WelcomeTourSettings::group(), WelcomeTourSettingsSchema::class);
        };

        $this->app->afterResolving(SettingsSchemaRegistry::class, $registerSettings);

        if ($this->app->resolved(SettingsSchemaRegistry::class)) {
            $registerSettings($this->app->make(SettingsSchemaRegistry::class));
        }
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
}
