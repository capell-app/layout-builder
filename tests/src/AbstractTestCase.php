<?php

declare(strict_types=1);

namespace Capell\Tests;

use Awcodes\BadgeableColumn\BadgeableColumnServiceProvider;
use Awcodes\Curator\CuratorServiceProvider;
use BezhanSalleh\FilamentShield\FilamentShieldServiceProvider;
use BezhanSalleh\FilamentShield\Support\Utils;
use Bkwld\Cloner\ServiceProvider as ClonerServiceProvider;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Camya\Filament\FilamentTitleWithSlugServiceProvider;
use Capell\Core\CapellCoreManager;
use Capell\Core\CapellServiceProvider;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageTranslation;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
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
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use Silber\PageCache;
use Spatie\LaravelData\LaravelDataServiceProvider;
use Spatie\LaravelRay\RayServiceProvider;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionServiceProvider;
use Spatie\Tags\TagsServiceProvider;
use src\Fixtures\Models\User;
use src\Fixtures\Policies\RolePolicy;
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
            putenv('VIEW_COMPILED_PATH=storage/framework/views/phpunit-parallel-'.getenv('TEST_TOKEN'));
        }

        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadPackageMigrations(
            __DIR__.'/../../vendor/capell-app/core/database/migrations',
            CapellCoreManager::getMigrations()
        );

        if ($this->packageMigrations) {
            $this->loadMigrationsFrom($this->packageMigrations);
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

    protected function getEnvironmentSetUp($app): void
    {
        $this->registerPackageConfigs();

        Gate::policy(Utils::getRoleModel(), RolePolicy::class);
    }

    /**
     * Set up the database.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function setUpDatabase()
    {
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    }

    protected function getPackageProviders($app): array
    {
        return [
            ActionsServiceProvider::class,
            BadgeableColumnServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeCountryFlagsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            ClonerServiceProvider::class,
            CuratorServiceProvider::class,
            SpatieTranslatableServiceProvider::class,
            FilamentAuthenticationLogServiceProvider::class,
            FilamentServiceProvider::class,
            FilamentShieldServiceProvider::class,
            FilamentTitleWithSlugServiceProvider::class,
            FormsServiceProvider::class,
            SchemasServiceProvider::class,
            LaravelDataServiceProvider::class,
            LivewireServiceProvider::class,
            NestedSetServiceProvider::class,
            NotificationsServiceProvider::class,
            PageCache\LaravelServiceProvider::class,
            PermissionServiceProvider::class,
            RayServiceProvider::class,
            SupportServiceProvider::class,
            CapellServiceProvider::class,
            TablesServiceProvider::class,
            TagsServiceProvider::class,
            WidgetsServiceProvider::class,
        ];
    }

    protected function registerPackageConfigs(?array $packages = null)
    {
        if ($packages === null || $packages === []) {
            $packages = $this->getDefaultPackages();
        }

        foreach ($packages as $package_key => $package) {
            $config = require __DIR__.'/..'.$this->getPackageFile($package);

            $this->registerPackageConfig($package_key, $config);
        }

        // config('filament-shield.register_role_policy.enabled', false);
        Config::set('filament-shield.auth_provider_model.fqcn', User::class);

        // Prevent role being assigned to created user
        Config::set('filament-shield.panel_user.enabled', false);

        Config::set('auth.providers.users.model', User::class);

        Config::set('filesystems.disks.page_cache', [
            'driver' => 'local',
            'root' => public_path('page-cache'),
            'throw' => false,
        ]);
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
        ];
    }

    protected function setupPage(Page $page, Collection $languages): void
    {
        $languages->each(function (int $languageId) use ($page): void {
            $page->translations()->save(PageTranslation::factory()->make([
                'language_id' => $languageId,
                'title' => Str::title($page->name.' '.$languageId),
                'slug' => Str::slug($page->name.' '.$languageId),
            ]));
        });

        $page->refresh();
    }

    protected function getPackageMigrations(string $path, array $migrations): array
    {
        $path = realpath($path);

        array_walk($migrations, fn (&$migration): string => $migration = sprintf('%s/%s.php', $path, $migration));

        return $migrations;
    }

    protected function loadPackageMigrations(string $path, array $migrations): void
    {
        $this->loadMigrationsFrom($this->getPackageMigrations($path, $migrations));
    }

    private function getPackageFile(array $package): string
    {
        $path = '/../vendor/'.basename((string) $package['user']).'/'.basename((string) $package['name']).'/config';
        $file = basename((string) $package['file']).'.php';

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
}
