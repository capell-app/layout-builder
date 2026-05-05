<?php

declare(strict_types=1);

namespace Capell\PasswordSecurity\Providers;

use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\Admin\Contracts\Extenders\UserFormExtender;
use Capell\Admin\Contracts\Extenders\UserTableExtender;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsGroupMetadata;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\PasswordSecurity\Filament\Extenders\PasswordSecurityPanelExtender;
use Capell\PasswordSecurity\Filament\Extenders\PasswordSecurityUserFormExtender;
use Capell\PasswordSecurity\Filament\Extenders\PasswordSecurityUserTableExtender;
use Capell\PasswordSecurity\Filament\Pages\ForcedPasswordChangePage;
use Capell\PasswordSecurity\Filament\Pages\PasswordSecuritySettingsPage;
use Capell\PasswordSecurity\Filament\Settings\PasswordSecuritySettingsSchema;
use Capell\PasswordSecurity\Settings\PasswordSecuritySettings;
use Composer\InstalledVersions;
use Filament\Support\Icons\Heroicon;
use Spatie\LaravelPackageTools\Package;

class PasswordSecurityServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-password-security';

    public static string $packageName = 'capell-app/password-security';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile()
            ->hasViews(self::$name)
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
            setting: PasswordSecuritySettings::class,
            description: fn (): string => __('capell-password-security::package.description'),
        );

        if (! config('capell-password-security.enabled', true)) {
            return;
        }

        $this
            ->registerSettingsWhenRegistryIsReady()
            ->registerAdminSurface()
            ->registerConfigSettings();
    }

    private function registerSettingsWhenRegistryIsReady(): self
    {
        $this->app->afterResolving(
            SettingsSchemaRegistry::class,
            fn (SettingsSchemaRegistry $registry): SettingsSchemaRegistry => $this->registerSettings($registry),
        );

        if ($this->app->resolved(SettingsSchemaRegistry::class)) {
            $this->registerSettings(resolve(SettingsSchemaRegistry::class));
        }

        return $this;
    }

    private function registerSettings(SettingsSchemaRegistry $registry): SettingsSchemaRegistry
    {
        $registry->registerSettingsClass('password_security', PasswordSecuritySettings::class);
        $registry->registerMetadata(new SettingsGroupMetadata(
            group: 'password_security',
            label: 'capell-password-security::settings.title',
            icon: Heroicon::OutlinedKey,
            navigationGroup: 'capell-admin::navigation.group_administration',
            navigationSort: 93,
            packageName: static::$packageName,
        ));
        $registry->register('password_security', PasswordSecuritySettingsSchema::class);

        return $registry;
    }

    private function registerAdminSurface(): self
    {
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(PasswordSecuritySettingsPage::class));
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(ForcedPasswordChangePage::class));

        $this->app->tag(PasswordSecurityPanelExtender::class, AdminPanelExtender::TAG);
        $this->app->tag(PasswordSecurityUserFormExtender::class, UserFormExtender::TAG);
        $this->app->tag(PasswordSecurityUserTableExtender::class, UserTableExtender::TAG);

        return $this;
    }

    private function registerConfigSettings(): self
    {
        $settings = config('settings.settings', []);

        if (! in_array(PasswordSecuritySettings::class, $settings, true)) {
            $settings[] = PasswordSecuritySettings::class;
        }

        config(['settings.settings' => $settings]);

        return $this;
    }

    private function getVersion(): string
    {
        if (! class_exists(InstalledVersions::class) || ! InstalledVersions::isInstalled(static::$packageName)) {
            return 'dev';
        }

        return InstalledVersions::getPrettyVersion(static::$packageName) ?? 'dev';
    }
}
