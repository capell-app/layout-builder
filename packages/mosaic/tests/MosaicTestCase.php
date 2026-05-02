<?php

declare(strict_types=1);

namespace Capell\Mosaic\Tests;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Providers\Filament\AdminPanelProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Media;
use Capell\DefaultTheme\Providers\DefaultThemeServiceProvider;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Mosaic\Providers\MosaicServiceProvider;
use Capell\Tests\AbstractTestCase;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Override;

class MosaicTestCase extends AbstractTestCase
{
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
    }

    protected function getPackageServiceName(): string
    {
        return 'capell-mosaic';
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            MosaicServiceProvider::class,
            AdminPanelProvider::class,
            AdminServiceProvider::class,
            FrontendServiceProvider::class,
            DefaultThemeServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FrontendServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(MosaicServiceProvider::$packageName);

        CapellCore::registerPackage('capell-app/navigation', path: realpath(__DIR__ . '/../../navigation'));
        CapellCore::forcePackageInstalled('capell-app/navigation');

        $app->make(Repository::class)->set('media-library.media_model', Media::class);
    }
}
