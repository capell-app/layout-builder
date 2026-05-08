<?php

declare(strict_types=1);

namespace Capell\PasswordPolicy\Providers;

use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\Admin\Contracts\Extenders\UserFormExtender;
use Capell\Admin\Contracts\Extenders\UserTableExtender;
use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsGroupMetadata;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\PasswordPolicy\Filament\Extenders\PasswordPolicyPanelExtender;
use Capell\PasswordPolicy\Filament\Extenders\PasswordPolicyUserFormExtender;
use Capell\PasswordPolicy\Filament\Extenders\PasswordPolicyUserTableExtender;
use Capell\PasswordPolicy\Filament\Pages\ForcedPasswordChangePage;
use Capell\PasswordPolicy\Filament\Pages\PasswordPolicySettingsPage;
use Capell\PasswordPolicy\Filament\Settings\PasswordPolicySettingsSchema;
use Capell\PasswordPolicy\Settings\PasswordPolicySettings;
use Composer\InstalledVersions;
use Filament\Support\Icons\Heroicon;
use Spatie\LaravelPackageTools\Package;

class PasswordPolicyServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-password-policy';

    public static string $packageName = 'capell-app/password-policy';

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
            setting: PasswordPolicySettings::class,
            description: fn (): string => __('capell-password-policy::package.description'),
        );

        if (! config('capell-password-policy.enabled', true)) {
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
        $registry->registerSettingsClass('password_policy', PasswordPolicySettings::class);
        if (method_exists($registry, 'registerMetadata')) {
            $metadataClass = SettingsGroupMetadata::class;

            if (class_exists($metadataClass)) {
                $registry->registerMetadata(new $metadataClass(
                    group: 'password_policy',
                    label: 'capell-password-policy::settings.title',
                    icon: Heroicon::OutlinedKey,
                    navigationGroup: 'capell-admin::navigation.group_administration',
                    navigationSort: 93,
                    packageName: static::$packageName,
                ));
            }
        }

        $registry->register('password_policy', PasswordPolicySettingsSchema::class);

        return $registry;
    }

    private function registerAdminSurface(): self
    {
        CapellAdmin::registerExtensionPage(
            static::$packageName,
            PasswordPolicySettingsPage::class,
        );

        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::page(ForcedPasswordChangePage::class));

        $this->app->tag(PasswordPolicyPanelExtender::class, AdminPanelExtender::TAG);

        if (interface_exists(UserFormExtender::class)) {
            $this->app->tag(PasswordPolicyUserFormExtender::class, UserFormExtender::TAG);
        }

        if (interface_exists(UserTableExtender::class)) {
            $this->app->tag(PasswordPolicyUserTableExtender::class, 'capell-admin:user-table-extender');
        }

        return $this;
    }

    private function registerConfigSettings(): self
    {
        $settings = config('settings.settings', []);

        if (! in_array(PasswordPolicySettings::class, $settings, true)) {
            $settings[] = PasswordPolicySettings::class;
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
