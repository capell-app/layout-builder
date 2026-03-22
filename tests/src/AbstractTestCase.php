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
use Capell\Core\Facades\CapellCore;
use Capell\Core\Providers\CapellServiceProvider;
use Capell\Tests\Fixtures\Models\User;
use Capell\Tests\Fixtures\Policies\RolePolicy;
use Capell\Tests\Support\Concerns\BuildsOrderedMigrationWorkspace;
use Capell\Tests\Support\Concerns\RegistersPublishedConfigs;
use CmsMulti\FilamentClearCache\FilamentClearCacheServiceProvider;
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
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Kalnoy\Nestedset\NestedSetServiceProvider;
use LaraZeus\SpatieTranslatable\SpatieTranslatableServiceProvider;
use Lorisleiva\Actions\ActionServiceProvider;
use MichalOravec\PaginateRoute\PaginateRouteServiceProvider;
use Oddvalue\LaravelDrafts\LaravelDraftsServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Orchestra\Workbench\WorkbenchServiceProvider;

use function Pest\Laravel\artisan;

use Saade\FilamentAdjacencyList\FilamentAdjacencyListServiceProvider;
use Silber\PageCache\LaravelServiceProvider;
use Sinnbeck\DomAssertions\DomAssertionsServiceProvider;
use Spatie\Activitylog\ActivitylogServiceProvider;
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
    use BuildsOrderedMigrationWorkspace;
    use InteractsWithSession;
    use LazilyRefreshDatabase;
    use RegistersPublishedConfigs;
    use WithFaker;
    use WithWorkbench;

    protected function setUp(): void
    {
        if (getenv('TEST_TOKEN')) {
            putenv('VIEW_COMPILED_PATH=storage/framework/views/phpunit-parallel-' . getenv('TEST_TOKEN'));
        }

        parent::setUp();

        Blade::componentNamespace('Capell\\Blog\\View\\Components', 'capell-blog');
        Blade::componentNamespace('Capell\\Layout\\View\\Components', 'capell-layout');

        $this->orderedMigrationWorkspacePath();

        Http::preventStrayRequests();

        Relation::morphMap([
            'user' => User::class,
        ]);

        Model::shouldBeStrict();

        $this->setUpDatabase();
    }

    protected function tearDown(): void
    {
        try {
            $this->cleanupOrderedMigrationWorkspace();
        } finally {
            parent::tearDown();
        }
    }

    protected function migrateDatabases(): void
    {
        $seeder = $this->seeder();
        $shouldUseSeederClass = is_string($seeder) && $seeder !== '';

        artisan('migrate:fresh', array_merge(
            [
                '--drop-views' => $this->shouldDropViews(),
                '--drop-types' => $this->shouldDropTypes(),
            ],
            $shouldUseSeederClass ? ['--seeder' => $seeder] : ['--seed' => $this->shouldSeed()],
        ));

        artisan('migrate', [
            '--path' => [$this->orderedMigrationWorkspacePath()],
            '--realpath' => true,
            '--force' => true,
        ]);
    }

    protected function migrateFreshUsing(): array
    {
        $seeder = $this->seeder();
        $shouldUseSeederClass = is_string($seeder) && $seeder !== '';

        return array_merge(
            [
                '--drop-views' => $this->shouldDropViews(),
                '--drop-types' => $this->shouldDropTypes(),
            ],
            $shouldUseSeederClass ? ['--seeder' => $seeder] : ['--seed' => $this->shouldSeed()],
        );
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

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders($app): array
    {
        return [
            WorkbenchServiceProvider::class,
            ActionServiceProvider::class,
            ActionsServiceProvider::class,
            BadgeableColumnServiceProvider::class,
            BladeCountryFlagsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            ClonerServiceProvider::class,
            SpatieTranslatableServiceProvider::class,
            SpatieLaravelSettingsPluginServiceProvider::class,
            TinyeditorServiceProvider::class,
            FilamentServiceProvider::class,
            SupportServiceProvider::class,
            InfolistsServiceProvider::class,
            FilamentAuthenticationLogServiceProvider::class,
            FilamentServiceProvider::class,
            FilamentAdjacencyListServiceProvider::class,
            FilamentShieldServiceProvider::class,
            FilamentSelectTreeServiceProvider::class,
            FilamentClearCacheServiceProvider::class,
            FormsServiceProvider::class,
            PaginateRouteServiceProvider::class,
            ActivitylogServiceProvider::class,
            LaravelDataServiceProvider::class,
            NestedSetServiceProvider::class,
            LaravelServiceProvider::class,
            PermissionServiceProvider::class,
            IconPickerServiceProvider::class,
            LaravelDraftsServiceProvider::class,
            RayServiceProvider::class,
            DomAssertionsServiceProvider::class,
            SpatieLaravelSettingsPluginServiceProvider::class,
            CapellServiceProvider::class,
            MediaLibraryServiceProvider::class,
            ActivitylogServiceProvider::class,
            LaravelSettingsServiceProvider::class,
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

        foreach ($packages as $packageKey => $package) {
            $config = require __DIR__ . '/..' . $this->getPackageFile($package);

            $this->registerPackageConfig($packageKey, $config);
        }

        Config::set('filament-shield.authenticable-resources', [User::class]);
        Config::set('filament-shield.auth_provider_model', User::class);
        CapellCore::registerModel('User', User::class);

        Config::set('filament-shield.panel_user.enabled', false);

        Config::set('auth.providers.users.model', User::class);

        Config::set('filesystems.disks.page_cache', [
            'driver' => 'local',
            'root' => public_path('page-cache'),
            'throw' => false,
        ]);

        if (getenv('TEST_TOKEN')) {
            putenv('VIEW_COMPILED_PATH=storage/framework/views/phpunit-parallel-' . getenv('TEST_TOKEN'));

            Config::set('settings.cache.prefix', 'settings-cache-' . getenv('TEST_TOKEN'));
        }
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
}
