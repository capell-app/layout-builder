<?php

declare(strict_types=1);

namespace Capell\AuthenticationLog\Providers;

use Capell\AuthenticationLog\Filament\Settings\AuthenticationLogSettingsSchema;
use Capell\AuthenticationLog\Http\Middleware\UserActivityMiddleware;
use Capell\AuthenticationLog\Models\AuthenticationLog;
use Capell\AuthenticationLog\Observers\AuthenticationLogObserver;
use Capell\AuthenticationLog\Settings\AuthenticationLogSettings;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog as VendorAuthenticationLog;
use Spatie\LaravelPackageTools\Package;

class AuthenticationLogServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-authentication-log';

    public static string $packageName = 'capell-app/authentication-log';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('authentication-log')
            ->hasTranslations()
            ->hasMigrations([
                'create_authentication_log_table',
            ]);
    }

    public function registeringPackage(): void
    {
        $this->app->register(AdminServiceProvider::class);
    }

    public function packageRegistered(): void
    {
        $this->registerPackageMetadata()
            ->registerModels()
            ->registerSettings()
            ->registerProtectedTables()
            ->registerMiddlewareAliases();
    }

    public function packageBooted(): void
    {
        VendorAuthenticationLog::observe(AuthenticationLogObserver::class);
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/../..'),
            version: CapellCore::getInstalledPrettyVersion(self::$packageName),
            description: fn (): string => __('capell-authentication-log::package.description'),
        );

        return $this;
    }

    private function registerModels(): self
    {
        Config::set('authentication-log.authentication_log_model', AuthenticationLog::class);

        CapellCore::registerModels([AuthenticationLog::class]);

        return $this;
    }

    private function registerSettings(): self
    {
        $this->app->afterResolving(
            SettingsSchemaRegistry::class,
            function (SettingsSchemaRegistry $registry): void {
                $registry->registerSettingsClass('authentication_log', AuthenticationLogSettings::class);
                $registry->register('authentication_log', AuthenticationLogSettingsSchema::class);
            },
        );

        return $this;
    }

    private function registerProtectedTables(): self
    {
        CapellCore::registerProtectedTable(fn (): string => config('authentication-log.table_name', 'authentication_log'));

        return $this;
    }

    private function registerMiddlewareAliases(): self
    {
        Route::aliasMiddleware('frontend.activity', UserActivityMiddleware::class);

        return $this;
    }
}
