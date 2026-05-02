<?php

declare(strict_types=1);

namespace Capell\Tests\Packages;

use Capell\Address\Providers\AddressServiceProvider;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Providers\Filament\AdminPanelProvider;
use Capell\Analytics\Providers\AnalyticsServiceProvider;
use Capell\AuthenticationLog\Providers\AuthenticationLogServiceProvider;
use Capell\Backup\Providers\BackupServiceProvider;
use Capell\Blog\Providers\BlogServiceProvider;
use Capell\Blog\Providers\FrontendServiceProvider as BlogFrontendServiceProvider;
use Capell\Campaigns\Providers\CampaignsServiceProvider;
use Capell\Core\Actions\RegisterBlazeOptimizedViewsAction;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Media;
use Capell\Core\Providers\CapellServiceProvider;
use Capell\DefaultTheme\Providers\DefaultThemeServiceProvider;
use Capell\DeveloperTools\Providers\DeveloperToolsServiceProvider;
use Capell\FilamentPeek\Providers\FilamentPeekServiceProvider;
use Capell\Forms\Providers\FormsServiceProvider as CapellFormsServiceProvider;
use Capell\Frontend\Contracts\SettingsMigrationProviderInterface;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\MediaCurator\CapellMediaCuratorServiceProvider;
use Capell\Mosaic\Providers\MosaicServiceProvider;
use Capell\Navigation\Providers\NavigationServiceProvider;
use Capell\Redirects\Providers\RedirectsServiceProvider;
use Capell\SeoTools\Providers\SeoToolsServiceProvider;
use Capell\SiteSearch\Providers\SiteSearchServiceProvider;
use Capell\Tags\Models\Tag;
use Capell\Tags\Providers\TagsServiceProvider;
use Capell\Tests\AbstractTestCase;
use Capell\ThemeStudio\Admin\ThemeStudioAdminServiceProvider;
use Capell\ThemeStudio\Core\ThemeStudioCoreServiceProvider;
use Capell\Toolbar\Providers\ToolbarServiceProvider;
use Capell\Workspaces\Providers\WorkspacesServiceProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;

class PackagesTestCase extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerBlazeOptimizedViews();

        $this->registerAndMigrateSettings(
            CapellCore::getSettingMigrations(),
            __DIR__ . '/../../vendor/capell-app/core/database/settings',
        );

        $this->registerAndMigrateSettings(
            CapellAdmin::getSettingMigrations(),
            __DIR__ . '/../../vendor/capell-app/admin/database/settings',
        );

        $this->registerAndMigrateSettings(
            resolve(SettingsMigrationProviderInterface::class)->getSettingMigrations(),
            __DIR__ . '/../../vendor/capell-app/frontend/database/settings',
        );
    }

    protected function getPackageServiceName(): string
    {
        return 'capell-packages';
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AddressServiceProvider::class,
            AnalyticsServiceProvider::class,
            AuthenticationLogServiceProvider::class,
            BackupServiceProvider::class,
            MosaicServiceProvider::class,
            NavigationServiceProvider::class,
            BlogServiceProvider::class,
            BlogFrontendServiceProvider::class,
            CampaignsServiceProvider::class,
            CapellFormsServiceProvider::class,
            DeveloperToolsServiceProvider::class,
            SeoToolsServiceProvider::class,
            SiteSearchServiceProvider::class,
            TagsServiceProvider::class,
            ToolbarServiceProvider::class,
            FilamentPeekServiceProvider::class,
            WorkspacesServiceProvider::class,
            RedirectsServiceProvider::class,
            CapellMediaCuratorServiceProvider::class,
            ThemeStudioAdminServiceProvider::class,
            ThemeStudioCoreServiceProvider::class,
            FrontendServiceProvider::class,
            CapellServiceProvider::class,
            AdminPanelProvider::class,
            AdminServiceProvider::class,
            DefaultThemeServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(MosaicServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(SeoToolsServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(TagsServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FrontendServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(AnalyticsServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(BackupServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(BlogServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(CampaignsServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(CapellFormsServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(DeveloperToolsServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(AddressServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(AuthenticationLogServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FilamentPeekServiceProvider::$packageName);
        CapellCore::forcePackageInstalled('capell-app/media-curator');
        CapellCore::forcePackageInstalled(RedirectsServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(SiteSearchServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(ThemeStudioAdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled('capell-app/theme-studio-core');
        CapellCore::forcePackageInstalled(ToolbarServiceProvider::$packageName);
        CapellCore::forcePackageInstalled('capell-app/workspaces');

        CapellCore::registerPackage('capell-app/navigation', path: realpath(__DIR__ . '/../../packages/navigation'));
        CapellCore::forcePackageInstalled('capell-app/navigation');

        $app->make(Repository::class)->set('tags.tag_model', Tag::class);
        $app->make(Repository::class)->set('media-library.media_model', Media::class);
    }

    private function registerBlazeOptimizedViews(): void
    {
        foreach ([
            __DIR__ . '/../../packages/blog/resources/views/components',
            __DIR__ . '/../../packages/mosaic/resources/views/components',
            __DIR__ . '/../../packages/seo-tools/resources/views/components/schema',
            __DIR__ . '/../../packages/default-theme/resources/views/components/button/index.blade.php',
            __DIR__ . '/../../packages/theme-default/resources/views/components',
        ] as $path) {
            RegisterBlazeOptimizedViewsAction::run($path);
        }
    }
}
