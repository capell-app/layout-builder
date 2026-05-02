<?php

declare(strict_types=1);

namespace Capell\SiteSearch\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\SiteSearch\Actions\ResolveSiteSearchSettingAction;
use Capell\SiteSearch\Contracts\SiteSearch;
use Capell\SiteSearch\Drivers\DatabaseSiteSearch;
use Capell\SiteSearch\Drivers\ScoutSiteSearch;
use Capell\SiteSearch\Enums\SearchDriver;
use Capell\SiteSearch\Filament\Settings\SiteSearchSettingsSchema;
use Capell\SiteSearch\Models\SiteSearchLog;
use Capell\SiteSearch\Settings\SiteSearchSettings;
use Capell\SiteSearch\Support\RenderHooks\RegisterHeaderSearchHook;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\ConnectionResolverInterface;
use Spatie\LaravelPackageTools\Package;

final class SiteSearchServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-site-search';

    public static string $packageName = 'capell-app/site-search';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('capell-site-search')
            ->hasTranslations();

        if (is_dir(__DIR__ . '/../../resources/views')) {
            $package->hasViews();
        }

        if (file_exists(__DIR__ . '/../../database/migrations/create_site_search_logs_table.php')) {
            $package->hasMigrations([
                'create_site_search_logs_table',
            ]);
        }

        if (file_exists(__DIR__ . '/../../routes/web.php')) {
            $package->hasRoute('web');
        }
    }

    public function registeringPackage(): void
    {
        $this->app->register(AdminServiceProvider::class);

        $this->registerSiteSearchBinding();
    }

    public function packageRegistered(): void
    {
        $this
            ->registerPackageMetadata()
            ->registerModels()
            ->registerSettings()
            ->registerProtectedTables();
    }

    public function packageBooted(): void
    {
        if (! class_exists(RegisterHeaderSearchHook::class)) {
            return;
        }

        $this->app->make(RegisterHeaderSearchHook::class)->register();
    }

    private function registerSiteSearchBinding(): self
    {
        if (! interface_exists(SiteSearch::class)) {
            return $this;
        }

        if (! class_exists(DatabaseSiteSearch::class)) {
            return $this;
        }

        $this->app->bind(SiteSearch::class, function (Application $app): SiteSearch {
            $driver = $this->resolveDriver();

            if ($driver === 'scout' && class_exists(ScoutSiteSearch::class)) {
                return new ScoutSiteSearch(
                    modelClass: config('capell-site-search.scout.model'),
                    urlColumn: config('capell-site-search.scout.url_column', 'slug'),
                    typeColumn: config('capell-site-search.scout.type_column', 'type'),
                    excerptLength: config('capell-site-search.excerpt_length', 200),
                );
            }

            return new DatabaseSiteSearch(
                db: $app->make(ConnectionResolverInterface::class)->connection(),
                table: config('capell-site-search.database.table', 'pages'),
                columns: config('capell-site-search.database.columns', ['title', 'excerpt', 'body']),
                urlColumn: config('capell-site-search.database.url_column', 'slug'),
                typeColumn: config('capell-site-search.database.type_column', 'type'),
                titleColumn: config('capell-site-search.database.title_column', 'title'),
                excerptColumn: config('capell-site-search.database.excerpt_column', 'excerpt'),
                bodyColumn: config('capell-site-search.database.body_column', 'body'),
            );
        });

        return $this;
    }

    private function resolveDriver(): string
    {
        $configDriver = ResolveSiteSearchSettingAction::run(
            'driver',
            'capell-site-search.driver',
            SearchDriver::Database->value,
        );

        if ($configDriver instanceof SearchDriver) {
            return $configDriver->value;
        }

        if (is_string($configDriver) && $configDriver !== '') {
            return $configDriver;
        }

        return SearchDriver::Database->value;
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/../..'),
            version: CapellCore::getInstalledPrettyVersion(self::$packageName),
            description: fn (): string => __('capell-site-search::package.description'),
        );

        return $this;
    }

    private function registerModels(): self
    {
        if (! class_exists(SiteSearchLog::class)) {
            return $this;
        }

        CapellCore::registerModels([SiteSearchLog::class]);

        return $this;
    }

    private function registerSettings(): self
    {
        if (! class_exists(SettingsSchemaRegistry::class)) {
            return $this;
        }

        if (! class_exists(SiteSearchSettings::class) || ! class_exists(SiteSearchSettingsSchema::class)) {
            return $this;
        }

        /** @var SettingsSchemaRegistry $registry */
        $registry = $this->app->make(SettingsSchemaRegistry::class);

        $registry->registerSettingsClass('site_search', SiteSearchSettings::class);
        $registry->register('site_search', SiteSearchSettingsSchema::class);

        return $this;
    }

    private function registerProtectedTables(): self
    {
        CapellCore::registerProtectedTable(fn (): string => config('capell-site-search.logs.table_name', 'site_search_logs'));

        return $this;
    }
}
