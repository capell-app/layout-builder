<?php

declare(strict_types=1);

namespace Capell\Tests\Blog;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Blog\Models\Tag;
use Capell\Blog\Providers\BlogServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Frontend\Contracts\SettingsMigrationProviderInterface;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Layout\Providers\LayoutServiceProvider;
use Capell\Tests\AbstractTestCase;
use Capell\Tests\Fixtures\Admin\AdminPanelProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Override;

class BlogTestCase extends AbstractTestCase
{
    protected string $packageServiceName = 'capell-blog';

    protected function setUp(): void
    {
        parent::setUp();
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

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            LayoutServiceProvider::class,
            AdminServiceProvider::class,
            FrontendServiceProvider::class,
            BlogServiceProvider::class,
            AdminPanelProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    #[Override]
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(BlogServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FrontendServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(LayoutServiceProvider::$packageName);

        $app->make(Repository::class)->set('tags.tag_model', Tag::class);
    }
}
