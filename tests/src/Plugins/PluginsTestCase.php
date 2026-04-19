<?php

declare(strict_types=1);

namespace Capell\Tests\Plugins;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Plugins\Providers\PluginsServiceProvider;
use Capell\Tests\AbstractTestCase;
use Capell\Tests\Fixtures\Admin\AdminPanelProvider;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Override;
use RuntimeException;

class PluginsTestCase extends AbstractTestCase
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
        return 'capell-plugins';
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            PluginsServiceProvider::class,
            AdminPanelProvider::class,
            AdminServiceProvider::class,
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
        CapellCore::forcePackageInstalled(PluginsServiceProvider::$packageName);
    }

    protected function getFixturePath(string $relative): string
    {
        return dirname(__DIR__, 3) . '/packages/plugins/tests/fixtures/' . ltrim($relative, '/');
    }

    protected function loadFixture(string $relative): string
    {
        $path = $this->getFixturePath($relative);

        throw_unless(is_file($path), RuntimeException::class, 'Fixture not found: ' . $relative);

        $contents = file_get_contents($path);

        throw_if($contents === false, RuntimeException::class, 'Fixture could not be read: ' . $relative);

        return $contents;
    }
}
