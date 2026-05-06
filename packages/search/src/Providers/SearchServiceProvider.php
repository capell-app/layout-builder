<?php

declare(strict_types=1);

namespace Capell\Search\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\Search\Actions\ResolveSearchSettingAction;
use Capell\Search\Contracts\Search;
use Capell\Search\Drivers\DatabaseSearch;
use Capell\Search\Drivers\ScoutSearch;
use Capell\Search\Enums\SearchDriver;
use Capell\Search\Filament\Settings\SearchSettingsSchema;
use Capell\Search\Models\SearchLog;
use Capell\Search\Settings\SearchSettings;
use Capell\Search\Support\RenderHooks\RegisterHeaderSearchHook;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\ConnectionResolverInterface;
use Spatie\LaravelPackageTools\Package;

final class SearchServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-search';

    public static string $packageName = 'capell-app/search';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('capell-search')
            ->hasTranslations();

        if (is_dir(__DIR__ . '/../../resources/views')) {
            $package->hasViews();
        }

        if (file_exists(__DIR__ . '/../../database/migrations/create_search_logs_table.php')) {
            $package->hasMigrations([
                'create_search_logs_table',
            ]);
        }

        if (file_exists(__DIR__ . '/../../routes/web.php')) {
            $package->hasRoute('web');
        }
    }

    public function registeringPackage(): void
    {
        $this->app->register(AdminServiceProvider::class);
    }

    public function packageRegistered(): void
    {
        $this
            ->registerPackageMetadata();

        $this->app->booted(function (): void {
            if (! $this->isPackageInstalled()) {
                return;
            }

            $this
                ->registerSearchBinding()
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

        if (! class_exists(RegisterHeaderSearchHook::class)) {
            return;
        }

        $this->app->make(RegisterHeaderSearchHook::class)->register();
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(self::$packageName);
    }

    private function registerSearchBinding(): self
    {
        if (! interface_exists(Search::class)) {
            return $this;
        }

        if (! class_exists(DatabaseSearch::class)) {
            return $this;
        }

        $this->app->bind(Search::class, function (Application $app): Search {
            $driver = $this->resolveDriver();

            if ($driver === 'scout' && class_exists(ScoutSearch::class)) {
                return new ScoutSearch(
                    modelClass: config('capell-search.scout.model'),
                    urlColumn: config('capell-search.scout.url_column', 'slug'),
                    typeColumn: config('capell-search.scout.type_column', 'type'),
                    excerptLength: config('capell-search.excerpt_length', 200),
                );
            }

            return new DatabaseSearch(
                db: $app->make(ConnectionResolverInterface::class)->connection(),
                table: config('capell-search.database.table', 'pages'),
                columns: config('capell-search.database.columns', ['title', 'excerpt', 'body']),
                urlColumn: config('capell-search.database.url_column', 'slug'),
                typeColumn: config('capell-search.database.type_column', 'type'),
                titleColumn: config('capell-search.database.title_column', 'title'),
                excerptColumn: config('capell-search.database.excerpt_column', 'excerpt'),
                bodyColumn: config('capell-search.database.body_column', 'body'),
            );
        });

        return $this;
    }

    private function resolveDriver(): string
    {
        $configDriver = ResolveSearchSettingAction::run(
            'driver',
            'capell-search.driver',
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
            description: fn (): string => __('capell-search::package.description'),
        );

        return $this;
    }

    private function registerModels(): self
    {
        if (! class_exists(SearchLog::class)) {
            return $this;
        }

        CapellCore::registerModels([SearchLog::class]);

        return $this;
    }

    private function registerSettings(): self
    {
        if (! class_exists(SettingsSchemaRegistry::class)) {
            return $this;
        }

        if (! class_exists(SearchSettings::class) || ! class_exists(SearchSettingsSchema::class)) {
            return $this;
        }

        /** @var SettingsSchemaRegistry $registry */
        $registry = $this->app->make(SettingsSchemaRegistry::class);

        $registry->registerSettingsClass('search', SearchSettings::class);
        $registry->register('search', SearchSettingsSchema::class);

        return $this;
    }

    private function registerProtectedTables(): self
    {
        CapellCore::registerProtectedTable(fn (): string => config('capell-search.logs.table_name', 'search_logs'));

        return $this;
    }
}
