<?php

declare(strict_types=1);

namespace Capell\Workspaces\Tests;

use AmidEsfahani\FilamentTinyEditor\TinyeditorServiceProvider;
use Awcodes\BadgeableColumn\BadgeableColumnServiceProvider;
use BezhanSalleh\FilamentShield\FilamentShieldServiceProvider;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Providers\Filament\AdminPanelProvider;
use Capell\Blog\Providers\BlogServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Media;
use Capell\Frontend\Contracts\SettingsMigrationProviderInterface;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Tests\AbstractTestCase;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\Workspaces\Providers\AdminServiceProvider as WorkspacesAdminServiceProvider;
use Capell\Workspaces\Providers\ConsoleServiceProvider as WorkspacesConsoleServiceProvider;
use Capell\Workspaces\Providers\WorkspacesServiceProvider;
use Capell\Workspaces\WorkspaceContext;
use CmsMulti\FilamentClearCache\FilamentClearCacheServiceProvider;
use CodeWithDennis\FilamentSelectTree\FilamentSelectTreeServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Guava\IconPicker\IconPickerServiceProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Gate;
use LaraZeus\SpatieTranslatable\SpatieTranslatableServiceProvider;
use Livewire\LivewireServiceProvider;
use MichalOravec\PaginateRoute\PaginateRouteServiceProvider;
use Override;
use Pboivin\FilamentPeek\FilamentPeekServiceProvider;
use Saade\FilamentAdjacencyList\FilamentAdjacencyListServiceProvider;
use STS\FilamentImpersonate\FilamentImpersonateServiceProvider;
use Tanmuhittin\LaravelGoogleTranslate\LaravelGoogleTranslateServiceProvider;
use Tapp\FilamentAuthenticationLog\FilamentAuthenticationLogServiceProvider;

class WorkspacesTestCase extends AbstractTestCase
{
    use CreatesAdminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // NavigationServiceProvider is excluded from providers to avoid duplicate migrations
        // (BuildsOrderedMigrationWorkspace also discovers navigation's migrations). Register
        // the view namespace here so capell-navigation:: references resolve in tests.
        $this->app->make(Factory::class)->addNamespace(
            'capell-navigation',
            realpath(__DIR__ . '/../../../packages/navigation/resources/views') === false
                ? ''
                : realpath(__DIR__ . '/../../../packages/navigation/resources/views'),
        );

        $this->registerAndMigrateSettings(
            CapellCore::getSettingMigrations(),
            __DIR__ . '/../../../vendor/capell-app/core/database/settings',
        );

        $this->registerAndMigrateSettings(
            CapellAdmin::getSettingMigrations(),
            __DIR__ . '/../../../vendor/capell-app/admin/database/settings',
        );

        $this->registerAndMigrateSettings(
            resolve(SettingsMigrationProviderInterface::class)->getSettingMigrations(),
            __DIR__ . '/../../../vendor/capell-app/frontend/database/settings',
        );
    }

    protected function tearDown(): void
    {
        WorkspaceContext::clear();
        Model::clearBootedModels();
        parent::tearDown();
    }

    protected function getPackageServiceName(): string
    {
        return 'capell-workspaces';
    }

    /**
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            ActionsServiceProvider::class,
            BadgeableColumnServiceProvider::class,
            SpatieTranslatableServiceProvider::class,
            TinyeditorServiceProvider::class,
            FilamentAuthenticationLogServiceProvider::class,
            FilamentServiceProvider::class,
            FilamentAdjacencyListServiceProvider::class,
            FilamentShieldServiceProvider::class,
            FilamentSelectTreeServiceProvider::class,
            FilamentClearCacheServiceProvider::class,
            FilamentPeekServiceProvider::class,
            FilamentImpersonateServiceProvider::class,
            FormsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            IconPickerServiceProvider::class,
            LaravelGoogleTranslateServiceProvider::class,
            SupportServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            NotificationsServiceProvider::class,
            AdminServiceProvider::class,
            AdminPanelProvider::class,
            FrontendServiceProvider::class,
            PaginateRouteServiceProvider::class,
            LivewireServiceProvider::class,
            WorkspacesServiceProvider::class,
            WorkspacesAdminServiceProvider::class,
            WorkspacesConsoleServiceProvider::class,
            BlogServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FrontendServiceProvider::$packageName);
        CapellCore::forcePackageInstalled('capell-app/workspaces');

        // Navigation and Tags have no capell.json so they're not auto-discovered;
        // register them explicitly so BuildsOrderedMigrationWorkspace loads their migrations.
        CapellCore::registerPackage('capell-app/navigation', path: realpath(__DIR__ . '/../../../packages/navigation'));
        CapellCore::forcePackageInstalled('capell-app/navigation');

        CapellCore::registerPackage('capell-app/tags', path: realpath(__DIR__ . '/../../../packages/tags'));
        CapellCore::forcePackageInstalled('capell-app/tags');

        CapellCore::registerPackage(BlogServiceProvider::$packageName, path: realpath(__DIR__ . '/../../../packages/blog'));
        CapellCore::forcePackageInstalled(BlogServiceProvider::$packageName);

        $app->make(Repository::class)->set('media-library.media_model', Media::class);

        // Shield's super_admin Gate::before bypass is normally registered by FilamentShieldPlugin.
        // Since AdminPanelProvider does not include that plugin, we register the bypass here so
        // permission checks in policies never throw PermissionDoesNotExist for super_admin users.
        Gate::before(
            fn (mixed $user, string $ability): ?bool => $user?->hasRole('super_admin') ? true : null,
        );
    }

    #[Override]
    protected function registerPackageConfigs(Application $app, ?array $packages = null): void
    {
        parent::registerPackageConfigs($app, $packages);

        $this->registerPublishConfig('admin');
        $this->registerPublishConfig('frontend');
    }
}
