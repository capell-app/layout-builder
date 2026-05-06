<?php

declare(strict_types=1);

namespace Capell\LoginAudit\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\LoginAudit\Filament\Settings\LoginAuditSettingsSchema;
use Capell\LoginAudit\Http\Middleware\UserActivityMiddleware;
use Capell\LoginAudit\Models\LoginAudit;
use Capell\LoginAudit\Observers\LoginAuditObserver;
use Capell\LoginAudit\Settings\LoginAuditSettings;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Rappasoft\LaravelLoginAudit\Models\LoginAudit as VendorLoginAudit;
use Spatie\LaravelPackageTools\Package;

class LoginAuditServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-login-audit';

    public static string $packageName = 'capell-app/login-audit';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('login-audit')
            ->hasTranslations()
            ->hasMigrations([
                'create_login_audit_table',
            ]);
    }

    public function registeringPackage(): void
    {
        $this->app->register(AdminServiceProvider::class);
    }

    public function packageRegistered(): void
    {
        $this->registerPackageMetadata();

        $this->app->booted(function (): void {
            if (! $this->isPackageInstalled()) {
                return;
            }

            $this
                ->registerModels()
                ->registerSettings()
                ->registerProtectedTables()
                ->registerMiddlewareAliases();
        });
    }

    public function packageBooted(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        VendorLoginAudit::observe(LoginAuditObserver::class);
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(static::$packageName);
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/../..'),
            version: CapellCore::getInstalledPrettyVersion(self::$packageName),
            description: fn (): string => __('capell-login-audit::package.description'),
        );

        return $this;
    }

    private function registerModels(): self
    {
        Config::set('login-audit.login_audit_model', LoginAudit::class);

        CapellCore::registerModels([LoginAudit::class]);

        return $this;
    }

    private function registerSettings(): self
    {
        /** @var SettingsSchemaRegistry $registry */
        $registry = $this->app->make(SettingsSchemaRegistry::class);

        $registry->registerSettingsClass('login_audit', LoginAuditSettings::class);
        $registry->register('login_audit', LoginAuditSettingsSchema::class);

        return $this;
    }

    private function registerProtectedTables(): self
    {
        CapellCore::registerProtectedTable(fn (): string => config('login-audit.table_name', 'login_audit'));

        return $this;
    }

    private function registerMiddlewareAliases(): self
    {
        Route::aliasMiddleware('frontend.activity', UserActivityMiddleware::class);

        return $this;
    }
}
