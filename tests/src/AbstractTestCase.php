<?php

declare(strict_types=1);

namespace Capell\Tests;

use AmidEsfahani\FilamentTinyEditor\TinyeditorServiceProvider;
use Awcodes\BadgeableColumn\BadgeableColumnServiceProvider;
use BezhanSalleh\FilamentShield\FilamentShieldServiceProvider;
use BezhanSalleh\FilamentShield\Support\Utils;
use Bkwld\Cloner\ServiceProvider as ClonerServiceProvider;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Capell\Core\CapellCoreManager;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageTranslation;
use Capell\Core\Providers\CapellServiceProvider;
use Capell\Tests\Fixtures\Models\User;
use Capell\Tests\Fixtures\Policies\RolePolicy;
use CodeWithDennis\FilamentSelectTree\FilamentSelectTreeServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\SpatieLaravelSettingsPluginServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Guava\IconPicker\IconPickerServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Kalnoy\Nestedset\NestedSetServiceProvider;
use LaraZeus\SpatieTranslatable\SpatieTranslatableServiceProvider;
use Oddvalue\LaravelDrafts\LaravelDraftsServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Orchestra\Workbench\WorkbenchServiceProvider;
use Rmsramos\Activitylog\ActivitylogServiceProvider;
use RuntimeException;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use Saade\FilamentAdjacencyList\FilamentAdjacencyListServiceProvider;
use Silber\PageCache\LaravelServiceProvider;
use Sinnbeck\DomAssertions\DomAssertionsServiceProvider;
use Spatie\LaravelData\LaravelDataServiceProvider;
use Spatie\LaravelRay\RayServiceProvider;
use Spatie\LaravelSettings\LaravelSettingsServiceProvider;
use Spatie\LaravelSettings\Migrations\SettingsMigration;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionServiceProvider;
use Spatie\Tags\TagsServiceProvider;
use StijnVanouplines\BladeCountryFlags\BladeCountryFlagsServiceProvider;
use Tapp\FilamentAuthenticationLog\FilamentAuthenticationLogServiceProvider;

abstract class AbstractTestCase extends TestCase
{
    use InteractsWithSession;
    use LazilyRefreshDatabase;
    use WithFaker;
    use WithWorkbench;

    protected array $packageMigrations = [];

    protected function setUp(): void
    {
        if (getenv('TEST_TOKEN')) {
            putenv('VIEW_COMPILED_PATH=storage/framework/views/phpunit-parallel-' . getenv('TEST_TOKEN'));
        }

        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $migrations = CapellCoreManager::getMigrations();
        $path = realpath(__DIR__ . '/../../vendor/capell-app/core/database/migrations');

        if (! $path) {
            $path = realpath(__DIR__ . '/../../vendor/capell-app/core/packages/core/database/migrations');
        }

        throw_unless($path, RuntimeException::class, 'Could not find core migrations path.');

        array_walk($migrations, fn (&$migration): string => $migration = sprintf('%s/%s.php', $path, $migration));

        $this->loadMigrationsFrom($migrations);

        // Load migrations for any explicitly required dependent packages.
        foreach ($this->requiredPackages() as $requiredPackage) {
            $this->packageMigrations = [
                ...$this->packageMigrations,
                ...$this->discoverPackageMigrations($requiredPackage),
            ];
        }

        if ($this->packageMigrations !== []) {
            $this->loadMigrationsFrom(array_values(array_unique($this->packageMigrations)));
        }

        Http::preventStrayRequests();

        Relation::morphMap([
            'user' => User::class,
        ]);

        Model::shouldBeStrict();

        // $this->app->setLocale('en_GB');

        $this->setUpDatabase();

        $this->withoutVite();
    }

    /**
     * Packages whose migrations are required in addition to the primary package.
     * Override in package test cases as needed (e.g. blog requires layout).
     *
     * @return string[]
     */
    protected function requiredPackages(): array
    {
        return [];
    }

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $this->registerPackageConfigs($app);

        Gate::policy(Utils::getRoleModel(), RolePolicy::class);
    }

    /**
     * Set up the database.
     *
     * @param  Application  $app
     */
    protected function setUpDatabase(): void
    {
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    }

    protected function getPackageProviders($app): array
    {
        return [
            WorkbenchServiceProvider::class,
            ActionsServiceProvider::class,
            BadgeableColumnServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeCountryFlagsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            ClonerServiceProvider::class,
            SpatieTranslatableServiceProvider::class,
            SpatieLaravelSettingsPluginServiceProvider::class,
            TinyeditorServiceProvider::class,
            FilamentServiceProvider::class,
            InfolistsServiceProvider::class,
            FilamentAuthenticationLogServiceProvider::class,
            FilamentServiceProvider::class,
            FilamentAdjacencyListServiceProvider::class,
            FilamentShieldServiceProvider::class,
            FilamentSelectTreeServiceProvider::class,
            ActivitylogServiceProvider::class,
            FormsServiceProvider::class,
            \Spatie\Activitylog\ActivitylogServiceProvider::class,
            LaravelDataServiceProvider::class,
            NestedSetServiceProvider::class,
            LaravelServiceProvider::class,
            PermissionServiceProvider::class,
            IconPickerServiceProvider::class,
            LaravelDraftsServiceProvider::class,
            RayServiceProvider::class,
            DomAssertionsServiceProvider::class,
            SupportServiceProvider::class,
            SchemasServiceProvider::class,
            CapellServiceProvider::class,
            LaravelSettingsServiceProvider::class,
            TablesServiceProvider::class,
            TagsServiceProvider::class,
            MediaLibraryServiceProvider::class,
            WidgetsServiceProvider::class,
            NotificationsServiceProvider::class,
        ];
    }

    protected function registerPackageConfigs(Application $app, ?array $packages = null): void
    {
        if ($packages === null || $packages === []) {
            $packages = $this->getDefaultPackages();
        }

        $this->registerPublishConfig('core', vendorPackage: true);
        $this->registerPublishConfig('admin', vendorPackage: true);

        foreach ($packages as $package_key => $package) {
            $config = require __DIR__ . '/..' . $this->getPackageFile($package);

            $this->registerPackageConfig($package_key, $config);
        }

        // config('filament-shield.register_role_policy.enabled', false);
        Config::set('filament-shield.auth_provider_model', User::class);

        // Prevent role being assigned to created user
        Config::set('filament-shield.panel_user.enabled', false);

        Config::set('auth.providers.users.model', User::class);

        Config::set('filesystems.disks.page_cache', [
            'driver' => 'local',
            'root' => public_path('page-cache'),
            'throw' => false,
        ]);

        // Spatie Permission testing flag for sqlite compatibility
        Config::set('permission.testing', true);
    }

    protected function getDefaultPackages(): array
    {
        return [
            'filament-shield' => [
                'user' => 'bezhansalleh',
                'name' => 'filament-shield',
                'file' => 'filament-shield',
            ],
            'authentication-log' => [
                'user' => 'rappasoft',
                'name' => 'laravel-authentication-log',
                'file' => 'authentication-log',
            ],
            'permission' => [
                'user' => 'spatie',
                'name' => 'laravel-permission',
                'file' => 'permission',
            ],
        ];
    }

    protected function registerPublishConfig(string $package, bool $vendorPackage = false): void
    {
        $configs = $this->getPublishConfigs($package, $vendorPackage);

        foreach ($configs as $configFile) {
            $config = require $configFile;
            $configName = basename((string) $configFile, '.php');

            $this->registerPackageConfig($configName, $config);
        }
    }

    protected function getPublishConfigs(string $package, bool $vendorPackage): array
    {
        if ($vendorPackage) {
            $path = realpath(__DIR__ . '/../../vendor/capell-app/' . $package . '/publishes/config');
        } else {
            $path = realpath(__DIR__ . '/../../packages/' . $package . '/publishes/config');
        }

        if (in_array($path, ['', '0', false], true)) {
            return [];
        }

        return glob($path . '/*.php');
    }

    protected function setupPage(Page $page, Collection $languages): void
    {
        $languages->each(function (int $languageId) use ($page): void {
            $page->translations()->save(PageTranslation::factory()->make([
                'language_id' => $languageId,
                'title' => Str::title($page->name . ' ' . $languageId),
                'slug' => Str::slug($page->name . ' ' . $languageId),
            ]));
        });

        $page->refresh();
    }

    protected function registerAndMigrateSettings(array $migrations, string $basePath): void
    {
        $migrator = resolve(SettingsMigrator::class);
        foreach ($migrations as $migrationFile) {
            $path = sprintf('%s/%s.php', $basePath, $migrationFile);
            /** @var SettingsMigration $migration */
            $migration = require $path;
            if (method_exists($migration, 'setMigrator')) {
                $migration->setMigrator($migrator);
            }

            $migration->up();
        }
    }

    private function getPackageFile(array $package): string
    {
        $path = '/../vendor/' . basename((string) $package['user']) . '/' . basename((string) $package['name']) . '/config';
        $file = basename((string) $package['file']) . '.php';

        return sprintf('%s/%s', $path, $file);
    }

    private function registerPackageConfig(string $package, array $config): void
    {
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $this->registerPackageConfig(sprintf('%s.%s', $package, $key), $value);

                continue;
            }

            config()->set(sprintf('%s.%s', $package, $key), $value);
        }
    }

    private function discoverPackageMigrations(string $package): array
    {
        $path = realpath(__DIR__ . '/../../packages/' . $package . '/database/migrations');
        $files = glob($path . '/*.php');

        return $files === false ? [] : $files;
    }
}
