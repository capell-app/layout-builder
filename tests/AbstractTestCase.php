<?php

declare(strict_types=1);

namespace Capell\Tests;

use Aimeos\Nestedset\NestedSetServiceProvider;
use AmidEsfahani\FilamentTinyEditor\TinyeditorServiceProvider;
use Awcodes\BadgeableColumn\BadgeableColumnServiceProvider;
use BezhanSalleh\FilamentShield\FilamentShieldServiceProvider;
use BezhanSalleh\FilamentShield\Support\Utils;
use Bkwld\Cloner\ServiceProvider as ClonerServiceProvider;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Capell\Address\Models\Address;
use Capell\Address\Models\Country;
use Capell\Admin\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\Blog\Models\Article;
use Capell\ContentSections\Models\Section;
use Capell\Core\Models\Widget;
use Capell\Core\Models\WidgetAsset;
use Capell\Core\Providers\CapellServiceProvider;
use Capell\FoundationTheme\View\Components\Widget\Page\Children;
use Capell\FoundationTheme\View\Components\Widget\Page\Content;
use Capell\FoundationTheme\View\Components\Widget\Page\Latest;
use Capell\FoundationTheme\View\Components\Widget\Page\Siblings;
use Capell\Tests\Fixtures\Models\User;
use Capell\Tests\Fixtures\Policies\RolePolicy;
use Capell\Tests\Support\Concerns\BuildsOrderedMigrationWorkspace;
use Capell\Tests\Support\Concerns\RegistersPublishedConfigs;
use Capell\Tests\Support\Concerns\TestingFrontendWithVite;
use CmsMulti\FilamentClearCache\FilamentClearCacheServiceProvider;
use CodeWithDennis\FilamentSelectTree\FilamentSelectTreeServiceProvider;
use Faker\Provider\Miscellaneous;
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
use LaraZeus\SpatieTranslatable\SpatieTranslatableServiceProvider;
use Lorisleiva\Actions\ActionServiceProvider;
use MichalOravec\PaginateRoute\PaginateRouteServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Orchestra\Workbench\WorkbenchServiceProvider;
use Saade\FilamentAdjacencyList\FilamentAdjacencyListServiceProvider;
use Silber\PageCache\LaravelServiceProvider;
use Sinnbeck\DomAssertions\DomAssertionsServiceProvider;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\ImageOptimizer\Optimizers\Svgo;
use Spatie\LaravelData\LaravelDataServiceProvider;
use Spatie\LaravelSettings\LaravelSettingsServiceProvider;
use Spatie\LaravelSettings\Migrations\SettingsMigration;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionServiceProvider;
use Spatie\Tags\TagsServiceProvider;
use StijnVanouplines\BladeCountryFlags\BladeCountryFlagsServiceProvider;
use STS\FilamentImpersonate\FilamentImpersonateServiceProvider;
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
            putenv('VIEW_COMPILED_PATH=storage/framework/views/phpunit-' . $this->getPackageServiceName() . '-parallel-' . getenv('TEST_TOKEN'));
        }

        parent::setUp();

        $this->faker->addProvider(new Miscellaneous($this->faker));

        if (! in_array(TestingFrontendWithVite::class, class_uses_recursive(static::class), true)) {
            $this->withoutVite();
        }

        Config::set('media-library.image_optimizers', [
            Svgo::class => [],
        ]);

        $this->loadMigrationsFrom($this->orderedMigrationWorkspacePath());

        // Temp fix to ensure components are locatable when run in parallel
        Blade::componentNamespace('Capell\\Blog\\View\\Components', 'capell-blog');
        Blade::componentNamespace('Capell\\FoundationTheme\\View\\Components', 'capell-layout-builder');
        Blade::component('capell-layout-builder::components.widget.page.breadcrumbs', 'capell-layout-builder-widget-page-breadcrumbs');
        Blade::component(Content::class, 'capell-layout-builder-widget-page-content');
        Blade::component('capell-layout-builder::components.widget.slot', 'capell-layout-builder-widget-slot');
        Blade::component(Children::class, 'capell-layout-builder::widget.page.children');
        Blade::component(Content::class, 'capell-layout-builder::widget.page.content');
        Blade::component(Latest::class, 'capell-layout-builder::widget.page.latest');
        Blade::component(Siblings::class, 'capell-layout-builder::widget.page.siblings');
        resolve('livewire.factory')->resolveMissingComponent(
            static fn (string $name): ?string => $name === 'capell-layout-builder::filament.layout-builder'
                ? LayoutBuilder::class
                : null,
        );

        Http::preventStrayRequests();

        Relation::morphMap([
            'address' => Address::class,
            'article' => Article::class,
            'country' => Country::class,
            'section' => Section::class,
            'user' => User::class,
            'widget' => Widget::class,
            'widget_asset' => WidgetAsset::class,
        ]);

        Model::shouldBeStrict();

        // $this->app->setLocale('en_GB');

        $this->setUpDatabase();
    }

    protected function tearDown(): void
    {
        try {
            $this->cleanupOrderedMigrationWorkspace();
            Model::clearBootedModels();
        } finally {
            parent::tearDown();
        }
    }

    abstract protected function getPackageServiceName(): string;

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp(mixed $app): void
    {
        $this->registerPackageConfigs($app);

        Gate::policy(Utils::getRoleModel(), RolePolicy::class);
    }

    /**
     * Set up the database.
     */
    protected function setUpDatabase(): void
    {
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders(mixed $app): array
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
            FilamentImpersonateServiceProvider::class,
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

        $this->registerPublishConfig('core');
        $this->registerPublishConfig('admin');
        $this->registerPublishConfig('frontend');

        foreach ($packages as $package_key => $package) {
            $config = require __DIR__ . '/..' . $this->getPackageFile($package);

            $this->registerPackageConfig($package_key, $config);
        }

        // config('filament-shield.register_role_policy.enabled', false);
        Config::set('filament-shield.authenticable-resources', [User::class]);
        Config::set('filament-shield.auth_provider_model', User::class);
        // Prevent role being assigned to created user
        Config::set('filament-shield.panel_user.enabled', false);

        // Route super_admin bypass through Gate::before so tests don't need
        // every Shield-generated permission seeded. With define_via_gate=false
        // (the package default) Shield expects `shield:generate` to have run
        // and the role to have been granted every permission — tests don't
        // run that pipeline, so any `hasPermissionTo('ViewAny:Layout')`-style
        // check from a registered policy throws PermissionDoesNotExist.
        Config::set('filament-shield.super_admin.define_via_gate', true);

        Config::set('auth.providers.users.model', User::class);

        $pageCacheDirectory = getenv('TEST_TOKEN')
            ? 'page-cache-' . getenv('TEST_TOKEN')
            : 'page-cache';

        Config::set('filesystems.disks.page_cache', [
            'driver' => 'local',
            'root' => public_path($pageCacheDirectory),
            'throw' => false,
        ]);

        if (getenv('TEST_TOKEN')) {
            Config::set('settings.cache.prefix', 'settings-cache-' . getenv('TEST_TOKEN'));
        }
    }

    protected function getDefaultPackages(): array
    {
        return [
            'filament-shield' => [
                'user' => 'bezhansalleh',
                'name' => 'filament-shield',
                'file' => 'filament-shield',
            ],
            'login-audit' => [
                'user' => 'rappasoft',
                'name' => 'laravel-authentication-log',
                'file' => 'authentication-log',
            ],
            'permission' => [
                'user' => 'spatie',
                'name' => 'laravel-permission',
                'file' => 'permission',
            ],
            'settings' => [
                'user' => 'spatie',
                'name' => 'laravel-settings',
                'file' => 'settings',
            ],
        ];
    }

    protected function registerPublishConfig(string $package): void
    {
        $configs = $this->getPublishConfigs($package);

        foreach ($configs as $configFile) {
            $config = require $configFile;
            $configName = basename((string) $configFile, '.php');

            $this->registerPackageConfig($configName, $config);
        }
    }

    protected function getPublishConfigs(string $package): array
    {
        $path = realpath(__DIR__ . '/../packages/' . $package . '/publishes/config');

        if (in_array($path, ['', '0', false], true)) {
            return [];
        }

        return glob($path . '/*.php');
    }

    protected function registerAndMigrateSettings(array $migrations, string $basePath): void
    {
        $settingsMigrator = resolve(SettingsMigrator::class);
        foreach ($migrations as $migrationFile) {
            $path = sprintf('%s/%s.php', $basePath, $migrationFile);
            /** @var SettingsMigration $migration */
            $migration = require $path;
            if (method_exists($migration, 'setMigrationAssistant')) {
                $migration->setMigrationAssistant($settingsMigrator);
            }

            if (! property_exists($migration, 'migration')) {
                // @phpstan-ignore property.notFound
                $migration->migration = $settingsMigrator;
            }

            $migration->up();
        }
    }

    private function getPackageFile(array $package): string
    {
        $path = '/vendor/' . basename((string) $package['user']) . '/' . basename((string) $package['name']) . '/config';
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
}
